<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/db.php';

initSession();
$authUser = currentUser();

if (!$authUser) {
    header('Location: cnx.php');
    exit;
}

$authName = trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? ''));
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');

$pdo      = getPDO();
$userType = $authUser['type'] ?? 'client';

/* ── POST Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_facture'])) {
        $idAchat = (int) $_POST['generate_facture'];
        $pdo->prepare("INSERT INTO facture (idAchat, etat, dateFac) VALUES (?, 'non payée', NOW())")
            ->execute([$idAchat]);
        header('Location: dashboard.php?section=achats');
        exit;
    }
    if (isset($_POST['mark_paid'])) {
        $idFac = (int) $_POST['mark_paid'];
        $pdo->prepare("UPDATE facture SET etat='payée' WHERE idFac=?")->execute([$idFac]);
        header('Location: dashboard.php?section=achats');
        exit;
    }
    if (isset($_POST['save_promo'])) {
        $idArt = (int) $_POST['promo_idart'];
        $old   = (int) $_POST['promo_old'];
        $new   = (int) $_POST['promo_new'];
        if ($idArt > 0 && $old > 0 && $new > 0 && $new < $old) {
            $pdo->prepare("INSERT INTO promo (idArt, oldPrice, newPrice) VALUES (?,?,?)
                           ON DUPLICATE KEY UPDATE oldPrice=?, newPrice=?")
                ->execute([$idArt, $old, $new, $old, $new]);
        }
        header('Location: dashboard.php?section=promos');
        exit;
    }
    if (isset($_POST['update_stock'])) {
        $idArt = (int) $_POST['idArt'];
        $qty   = max(0, (int) $_POST['qty']);
        $pdo->prepare("UPDATE article SET nbDispo=? WHERE idArt=?")->execute([$qty, $idArt]);
        http_response_code(200);
        exit;
    }
}

/* ── Helper Functions ── */
function getStockStats(PDO $pdo): array {
    try {
        return $pdo->query("
            SELECT COUNT(*) as total_articles,
                   SUM(nbDispo)                                       as total_stock,
                   SUM(CASE WHEN nbDispo = 0  THEN 1 ELSE 0 END)     as out_of_stock,
                   SUM(CASE WHEN nbDispo < 10 THEN 1 ELSE 0 END)     as low_stock
            FROM article")->fetch();
    } catch (Exception $e) {
        error_log('Stock stats error: ' . $e->getMessage());
        return ['total_articles' => 0, 'total_stock' => 0, 'out_of_stock' => 0, 'low_stock' => 0];
    }
}

function getAllStockItems(PDO $pdo): array {
    try {
        return $pdo->query("
            SELECT a.idArt, a.intitule, a.nbDispo, c.intituleCat, a.prix
            FROM article a
            JOIN categorie c ON a.idCat = c.idCat
            ORDER BY a.nbDispo ASC")->fetchAll();
    } catch (Exception $e) {
        error_log('All stock items error: ' . $e->getMessage());
        return [];
    }
}

function getRecentInvoices(PDO $pdo, int $limit = 10): array {
    try {
        $stmt = $pdo->prepare("
            SELECT f.idFac, f.idAchat, f.etat, f.dateFac,
                   u.nom, u.prenom, a.total
            FROM facture f
            JOIN achat a       ON f.idAchat   = a.idAchat
            JOIN utilisateur u ON a.idClient  = u.idUtili
            ORDER BY f.dateFac DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Recent invoices error: ' . $e->getMessage());
        return [];
    }
}

function getInvoiceStats(PDO $pdo): array {
    try {
        return $pdo->query("
            SELECT COUNT(*) as total_invoices,
                   SUM(CASE WHEN etat='payée'     THEN 1 ELSE 0 END) as paid,
                   SUM(CASE WHEN etat='non payée' THEN 1 ELSE 0 END) as unpaid
            FROM facture")->fetch();
    } catch (Exception $e) {
        error_log('Invoice stats error: ' . $e->getMessage());
        return ['total_invoices' => 0, 'paid' => 0, 'unpaid' => 0];
    }
}

function getPromotions(PDO $pdo, int $limit = 10): array {
    try {
        $stmt = $pdo->prepare("
            SELECT p.idArt, a.intitule, a.prix, p.oldPrice, p.newPrice,
                   ROUND(((p.oldPrice - p.newPrice) / p.oldPrice * 100)) as discount_percent,
                   c.intituleCat
            FROM promo p
            JOIN article  a ON p.idArt  = a.idArt
            JOIN categorie c ON a.idCat = c.idCat
            ORDER BY discount_percent DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Promotions error: ' . $e->getMessage());
        return [];
    }
}

function getSalesData(PDO $pdo): array {
    try {
        return $pdo->query("
            SELECT DATE(dateAchat) as date,
                   SUM(total) as total_sales,
                   COUNT(*)   as orders
            FROM achat
            WHERE dateAchat >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(dateAchat)
            ORDER BY date DESC")->fetchAll();
    } catch (Exception $e) {
        error_log('Sales data error: ' . $e->getMessage());
        return [];
    }
}

function getTopProducts(PDO $pdo, int $limit = 5): array {
    try {
        $stmt = $pdo->prepare("
            SELECT a.idArt, a.intitule, SUM(aa.quantite) as total_sold,
                   a.prix, c.intituleCat
            FROM article_achat aa
            JOIN article   a ON aa.idArticle = a.idArt
            JOIN categorie c ON a.idCat      = c.idCat
            GROUP BY a.idArt, a.intitule, a.prix, c.intituleCat
            ORDER BY total_sold DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Top products error: ' . $e->getMessage());
        return [];
    }
}

function getUserStats(PDO $pdo): array {
    try {
        return $pdo->query("
            SELECT COUNT(*) as total_users,
                   SUM(CASE WHEN type='client'        THEN 1 ELSE 0 END) as clients,
                   SUM(CASE WHEN type='professionnel' THEN 1 ELSE 0 END) as professionals
            FROM utilisateur")->fetch();
    } catch (Exception $e) {
        error_log('User stats error: ' . $e->getMessage());
        return ['total_users' => 0, 'clients' => 0, 'professionals' => 0];
    }
}

/* ── Data Fetching ── */
$stockStats    = getStockStats($pdo);
$allStockItems = getAllStockItems($pdo);
$recentInvoices= getRecentInvoices($pdo, 10);
$invoiceStats  = getInvoiceStats($pdo);
$promotions    = getPromotions($pdo, 10);
$salesData     = getSalesData($pdo);
$topProducts   = getTopProducts($pdo, 5);
$userStats     = getUserStats($pdo);
$totalRevenue  = array_sum(array_column($salesData, 'total_sales'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<div class="app">

    <header class="site-header">
        <div class="logo">
            <span class="logo-bar"></span>
            <span class="logo-image">
                <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word">
            </span>
        </div>
    </header>

    <div class="dash-layout">

        <!-- ══════════════════ SIDEBAR ══════════════════ -->
        <aside class="dash-sidebar" id="dashSidebar">
            <div class="dash-sidebar-inner">

                <div class="dash-sidebar-header">
                    <div class="dash-avatar"><?= mb_strtoupper(mb_substr($authName, 0, 1)) ?></div>
                    <div>
                        <div class="dash-username"><?= htmlspecialchars($authName, ENT_QUOTES) ?></div>
                        <div class="dash-role">Professionnel</div>
                    </div>
                </div>

                <nav class="dash-nav">
                    <div class="dash-nav-group">
                        <span class="dash-nav-label">Gestion</span>

                        <a href="#section-clients" class="dash-nav-link" onclick="showSection('clients',this);return false;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Gestion des clients
                        </a>

                        <a href="#section-stock" class="dash-nav-link" onclick="showSection('stock',this);return false;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Gestion du stock
                        </a>

                        <a href="#section-achats" class="dash-nav-link" onclick="showSection('achats',this);return false;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                            Gestion des achats
                        </a>

                        <a href="#section-promos" class="dash-nav-link" onclick="showSection('promos',this);return false;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            Promotions &amp; Réductions
                        </a>
                    </div>

                    <div class="dash-nav-group">
                        <span class="dash-nav-label">Compte</span>

                        <a href="profile.php" class="dash-nav-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            Mon profil
                        </a>

                        <a href="catalogue.php" class="dash-nav-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                            Retour au catalogue
                        </a>

                        <a href="logout.php" class="dash-nav-link" style="color:#ef4444">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Déconnexion
                        </a>
                    </div>
                </nav>

            </div>
        </aside>
        <!-- /sidebar -->

        <!-- ══════════════════ MAIN ══════════════════ -->
        <main class="dashboard-main">
        <div class="dashboard-container">

            <!-- ── Clients ── -->
            <div id="section-clients" style="display:none">
                <div class="dashboard-header">
                    <h1>Gestion des clients</h1>
                    <p>Liste et gestion de tous les utilisateurs inscrits</p>
                </div>
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            Clients inscrits
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nom complet</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Inscrit le</th>
                                    <th>Dernière connexion</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            try {
                                $stmtU = $pdo->query("SELECT idUtili, nom, prenom, email, numTel, created_at, last_login
                                                      FROM utilisateur
                                                      WHERE type = 'client'
                                                      ORDER BY created_at DESC");
                                $clientRows = $stmtU->fetchAll();
                                if (empty($clientRows)) {
                                    echo '<tr><td colspan="6" class="text-center">Aucun client inscrit</td></tr>';
                                } else {
                                    foreach ($clientRows as $u): ?>
                                    <tr>
                                        <td><strong>#<?= (int)$u['idUtili'] ?></strong></td>
                                        <td><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars($u['numTel'] ?? '—') ?></td>
                                        <td><?= $u['created_at'] ? date('d/m/Y', strtotime($u['created_at'])) : '—' ?></td>
                                        <td>
                                            <?php if ($u['last_login']): ?>
                                                <?= date('d/m/Y H:i', strtotime($u['last_login'])) ?>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted)">Jamais</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach;
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="6" class="text-center">Erreur de chargement</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <!-- /clients -->

            <!-- ── Stock ── -->
            <div id="section-stock" style="display:none">
                <div class="dashboard-header">
                    <h1>Gestion du Stock</h1>
                    <p>Suivi et modification des quantités d'articles disponibles</p>
                </div>
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                            Tous les Articles
                        </h2>
                    </div>

                    <div class="stock-summary">
                        <div class="summary-item">
                            <span>Total en stock</span>
                            <strong><?= number_format((int)($stockStats['total_stock'] ?? 0)) ?></strong>
                        </div>
                        <div class="summary-item warning">
                            <span>Stock faible (&lt; 10)</span>
                            <strong><?= (int)($stockStats['low_stock'] ?? 0) ?></strong>
                        </div>
                        <div class="summary-item danger">
                            <span>Rupture de stock</span>
                            <strong><?= (int)($stockStats['out_of_stock'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($allStockItems)): ?>
                                <?php foreach ($allStockItems as $item): ?>
                                <tr data-art-id="<?= (int)$item['idArt'] ?>">
                                    <td><strong><?= htmlspecialchars($item['intitule']) ?></strong></td>
                                    <td><?= htmlspecialchars($item['intituleCat']) ?></td>
                                    <td><?= number_format((float)$item['prix'], 0, ',', ' ') ?> DA</td>
                                    <td class="stock-qty"><?= (int)$item['nbDispo'] ?> unités</td>
                                    <td>
                                        <?php if ($item['nbDispo'] == 0): ?>
                                            <span class="badge danger">Rupture</span>
                                        <?php elseif ($item['nbDispo'] < 10): ?>
                                            <span class="badge warning">Faible</span>
                                        <?php else: ?>
                                            <span class="badge success">En stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-small"
                                                onclick="editStock(<?= (int)$item['idArt'] ?>, <?= (int)$item['nbDispo'] ?>)">
                                            Modifier
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">Aucun article trouvé</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <!-- /stock -->

            <!-- ── Achats ── -->
            <div id="section-achats" style="display:none">
                <div class="dashboard-header">
                    <h1>Gestion des achats</h1>
                    <p>Suivi des commandes et consultation des factures</p>
                </div>
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                            Commandes récentes
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#Achat</th>
                                    <th>Client</th>
                                    <th>Articles</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Facture</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            try {
                                $stmtA = $pdo->query("
                                    SELECT a.idAchat, a.total, a.dateAchat,
                                           u.nom, u.prenom, u.email,
                                           f.idFac, f.etat, f.dateFac,
                                           COUNT(aa.idArticle) as nb_articles
                                    FROM achat a
                                    JOIN utilisateur u      ON a.idClient  = u.idUtili
                                    LEFT JOIN facture f     ON f.idAchat   = a.idAchat
                                    LEFT JOIN article_achat aa ON aa.idAchat = a.idAchat
                                    GROUP BY a.idAchat, a.total, a.dateAchat,
                                             u.nom, u.prenom, u.email,
                                             f.idFac, f.etat, f.dateFac
                                    ORDER BY a.dateAchat DESC
                                    LIMIT 30");
                                $achats = $stmtA->fetchAll();

                                if (empty($achats)) {
                                    echo '<tr><td colspan="7" class="text-center">Aucune commande</td></tr>';
                                } else {
                                    foreach ($achats as $achat):
                                        $orderRef = 'BQ-' . date('Y', strtotime($achat['dateAchat'])) . '-' . str_pad((string)$achat['idAchat'], 4, '0', STR_PAD_LEFT);
                                        $clientName = htmlspecialchars($achat['prenom'] . ' ' . $achat['nom']);
                                        $totalFmt   = number_format((float)$achat['total'], 0, ',', ' ');
                                        $dateFmt    = date('d/m/Y H:i', strtotime($achat['dateAchat']));
                                ?>
                                <tr>
                                    <td><strong><?= $orderRef ?></strong></td>
                                    <td>
                                        <div><strong><?= $clientName ?></strong></div>
                                        <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($achat['email']) ?></div>
                                    </td>
                                    <td><?= (int)$achat['nb_articles'] ?> réf.</td>
                                    <td><strong><?= $totalFmt ?> DA</strong></td>
                                    <td><?= $dateFmt ?></td>
                                    <td>
                                        <?php if ($achat['idFac']): ?>
                                            <span class="badge <?= $achat['etat'] === 'payée' ? 'success' : 'warning' ?>">
                                                <?= $achat['etat'] === 'payée' ? 'Payée' : 'Non payée' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge alert">Non générée</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="td-actions">
                                        <?php if (!$achat['idFac']): ?>
                                            <form method="post" style="display:inline">
                                                <input type="hidden" name="generate_facture" value="<?= (int)$achat['idAchat'] ?>">
                                                <button type="submit" class="btn-small">Générer</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn-small"
                                                    onclick="openFacture(
                                                        <?= (int)$achat['idFac'] ?>,
                                                        <?= (int)$achat['idAchat'] ?>,
                                                        '<?= addslashes($clientName) ?>',
                                                        '<?= $totalFmt ?>',
                                                        '<?= $dateFmt ?>',
                                                        '<?= addslashes($achat['etat']) ?>')">
                                                Consulter
                                            </button>
                                            <?php if ($achat['etat'] === 'non payée'): ?>
                                            <form method="post" style="display:inline">
                                                <input type="hidden" name="mark_paid" value="<?= (int)$achat['idFac'] ?>">
                                                <button type="submit" class="btn-small success-btn">Marquer payée</button>
                                            </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="7" class="text-center">Erreur : ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <!-- /achats -->

            <!-- ── Promotions ── -->
            <div id="section-promos" style="display:none">
                <div class="dashboard-header">
                    <h1>Promotions &amp; Réductions</h1>
                    <p>Gérez les offres et remises sur les articles</p>
                </div>
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            Promotions actives
                        </h2>
                        <button class="btn-small" onclick="document.getElementById('add-promo-form').classList.toggle('hidden')">
                            + Ajouter
                        </button>
                    </div>

                    <!-- Add promo form -->
                    <form method="post" id="add-promo-form" class="add-promo-form hidden">
                        <div>
                            <label for="promo_idart">Article (ID)</label>
                            <input id="promo_idart" type="number" name="promo_idart" placeholder="ID article" class="form-input" min="1" required>
                        </div>
                        <div>
                            <label for="promo_old">Ancien prix (DA)</label>
                            <input id="promo_old" type="number" name="promo_old" placeholder="Ex : 5000" class="form-input" min="1" required>
                        </div>
                        <div>
                            <label for="promo_new">Nouveau prix (DA)</label>
                            <input id="promo_new" type="number" name="promo_new" placeholder="Ex : 3500" class="form-input" min="1" required>
                        </div>
                        <button type="submit" name="save_promo" class="btn-small">Enregistrer</button>
                    </form>

                    <div class="promotions-grid">
                        <?php foreach ($promotions as $promo): ?>
                        <div class="promo-card">
                            <div class="promo-badge">-<?= (int)$promo['discount_percent'] ?>%</div>
                            <h3><?= htmlspecialchars($promo['intitule']) ?></h3>
                            <p class="category"><?= htmlspecialchars($promo['intituleCat']) ?></p>
                            <div class="promo-prices">
                                <span class="old-price"><?= number_format((float)$promo['oldPrice'], 0, ',', ' ') ?> DA</span>
                                <span class="new-price"><?= number_format((float)$promo['newPrice'], 0, ',', ' ') ?> DA</span>
                            </div>
                            <button class="btn-small" onclick="editPromo(<?= (int)$promo['idArt'] ?>)">Modifier</button>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($promotions)): ?>
                            <div class="empty-state"><p>Aucune promotion active</p></div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            <!-- /promos -->

        </div><!-- /.dashboard-container -->
        </main>
        <!-- /main -->

    </div><!-- /.dash-layout -->

    <footer class="site-footer">
        <p>© 2026 BibenQuinc — Tous droits réservés</p>
    </footer>

</div><!-- /.app -->

<!-- ══════════════════ MODAL : Facture ══════════════════ -->
<div id="factureModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="fac-modal-title">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="fac-modal-title">
                Facture <span id="fac-id"></span>
            </h3>
            <button class="modal-close" onclick="closeFacture()" aria-label="Fermer">×</button>
        </div>

        <div class="fac-meta">
            <div class="fac-meta-item">
                <span class="fac-meta-label">Commande</span>
                <span class="fac-meta-value" id="fac-order"></span>
            </div>
            <div class="fac-meta-item">
                <span class="fac-meta-label">Client</span>
                <span class="fac-meta-value" id="fac-client"></span>
            </div>
            <div class="fac-meta-item">
                <span class="fac-meta-label">Date</span>
                <span class="fac-meta-value" id="fac-date"></span>
            </div>
            <div class="fac-meta-item">
                <span class="fac-meta-label">Statut</span>
                <span class="fac-meta-value"><span id="fac-etat" class="badge"></span></span>
            </div>
        </div>

        <p class="fac-items-title">Articles commandés</p>
        <div id="fac-items"></div>

        <div class="modal-footer">
            <div style="font-size:18px;font-weight:800;color:var(--accent)">
                Total : <span id="fac-total"></span>
            </div>
            <button class="btn-ghost" onclick="closeFacture()">Fermer</button>
        </div>
    </div>
</div>

<!-- ══════════════════ MODAL : Modifier stock ══════════════════ -->
<div id="stockModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="stock-modal-title">
    <div class="modal-box" style="max-width:360px">
        <div class="modal-header">
            <h3 id="stock-modal-title">Modifier le stock</h3>
            <button class="modal-close" onclick="closeStockModal()" aria-label="Fermer">×</button>
        </div>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:16px">
            Produit <strong id="stock-product-id"></strong>
        </p>
        <input id="stock-qty-input"
               type="number"
               class="stock-qty-field"
               min="0"
               placeholder="Nouvelle quantité"
               onkeydown="if(event.key==='Enter')submitStockUpdate()">
        <p class="stock-hint" style="margin-top:8px">Entrez 0 pour indiquer une rupture de stock.</p>
        <div class="modal-footer">
            <button class="btn-ghost" onclick="closeStockModal()">Annuler</button>
            <button id="stock-save-btn" class="btn-primary" onclick="submitStockUpdate()">Enregistrer</button>
        </div>
    </div>
</div>

<!-- ══════════════════ MODAL : Modifier promotion ══════════════════ -->
<div id="editPromoModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-promo-title">
    <div class="modal-box" style="max-width:420px">
        <div class="modal-header">
            <h3 id="edit-promo-title">Modifier la promotion</h3>
            <button class="modal-close" onclick="closeEditPromo()" aria-label="Fermer">×</button>
        </div>
        <form method="post">
            <p style="color:var(--text-muted);font-size:14px;margin-bottom:16px">
                Produit <strong id="edit-promo-id-label"></strong>
            </p>
            <input type="hidden" name="promo_idart">
            <div style="display:grid;gap:14px">
                <div>
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px">
                        Ancien prix (DA)
                    </label>
                    <input type="number" name="promo_old" class="form-input" placeholder="Ex : 5000" min="1" required>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px">
                        Nouveau prix (DA)
                    </label>
                    <input type="number" name="promo_new" class="form-input" placeholder="Ex : 3500" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="closeEditPromo()">Annuler</button>
                <button type="submit" name="save_promo" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script src="dashboard.js"></script>
</body>
</html>