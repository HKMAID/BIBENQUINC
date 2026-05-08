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

$pdo = getPDO();
$userType = $authUser['type'] ?? 'client';

// ── POST Actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_facture'])) {
        $idAchat = (int) $_POST['generate_facture'];
        $pdo->prepare("INSERT INTO facture (idAchat, etat, dateFac) VALUES (?, 'non payée', NOW())")->execute([$idAchat]);
        header('Location: dashboard.php?section=achats'); exit;
    }
    if (isset($_POST['save_promo'])) {
        $idArt = (int) $_POST['promo_idart'];
        $old   = (int) $_POST['promo_old'];
        $new   = (int) $_POST['promo_new'];
        if ($idArt > 0 && $old > 0 && $new > 0) {
            $pdo->prepare("INSERT INTO promo (idArt, oldPrice, newPrice) VALUES (?,?,?) ON DUPLICATE KEY UPDATE oldPrice=?, newPrice=?")->execute([$idArt,$old,$new,$old,$new]);
        }
        header('Location: dashboard.php?section=promos'); exit;
    }
    if (isset($_POST['update_stock'])) {
        $idArt = (int) $_POST['idArt'];
        $qty   = max(0, (int) $_POST['qty']);
        $pdo->prepare("UPDATE article SET nbDispo=? WHERE idArt=?")->execute([$qty, $idArt]);
        http_response_code(200); exit;
    }
}


// Helper Functions
function getStockStats($pdo) {
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_articles,
            SUM(nbDispo) as total_stock,
            SUM(CASE WHEN nbDispo = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN nbDispo < 5 THEN 1 ELSE 0 END) as low_stock
        FROM article");
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log('Stock stats error: ' . $e->getMessage());
        return ['total_articles' => 0, 'total_stock' => 0, 'out_of_stock' => 0, 'low_stock' => 0];
    }
}

function getLowStockItems($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("SELECT a.idArt, a.intitule, a.nbDispo, c.intituleCat, a.prix
            FROM article a
            JOIN categorie c ON a.idCat = c.idCat
            WHERE a.nbDispo < 10
            ORDER BY a.nbDispo ASC
            LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Low stock items error: ' . $e->getMessage());
        return [];
    }
}

function getRecentInvoices($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("SELECT f.idFac, f.idAchat, f.etat, f.dateFac, u.nom, u.prenom, a.total
            FROM facture f
            JOIN achat a ON f.idAchat = a.idAchat
            JOIN utilisateur u ON a.idClient = u.idUtili
            ORDER BY f.dateFac DESC
            LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Recent invoices error: ' . $e->getMessage());
        return [];
    }
}

function getInvoiceStats($pdo) {
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_invoices,
            SUM(CASE WHEN etat = 'payée' THEN 1 ELSE 0 END) as paid,
            SUM(CASE WHEN etat = 'non payée' THEN 1 ELSE 0 END) as unpaid
        FROM facture");
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log('Invoice stats error: ' . $e->getMessage());
        return ['total_invoices' => 0, 'paid' => 0, 'unpaid' => 0];
    }
}

function getPromotions($pdo, $limit = 8) {
    try {
        $stmt = $pdo->prepare("SELECT p.idArt, a.intitule, a.prix, p.oldPrice, p.newPrice, 
            ROUND(((p.oldPrice - p.newPrice) / p.oldPrice * 100)) as discount_percent,
            c.intituleCat
        FROM promo p
        JOIN article a ON p.idArt = a.idArt
        JOIN categorie c ON a.idCat = c.idCat
        ORDER BY discount_percent DESC
        LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Promotions error: ' . $e->getMessage());
        return [];
    }
}

function getSalesData($pdo) {
    try {
        $stmt = $pdo->query("SELECT DATE(dateAchat) as date, SUM(total) as total_sales, COUNT(*) as orders
        FROM achat
        WHERE dateAchat >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(dateAchat)
        ORDER BY date DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Sales data error: ' . $e->getMessage());
        return [];
    }
}

function getTopProducts($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("SELECT a.idArt, a.intitule, SUM(aa.quantite) as total_sold,
            a.prix, c.intituleCat
        FROM article_achat aa
        JOIN article a ON aa.idArticle = a.idArt
        JOIN categorie c ON a.idCat = c.idCat
        GROUP BY a.idArt, a.intitule, a.prix, c.intituleCat
        ORDER BY total_sold DESC
        LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Top products error: ' . $e->getMessage());
        return [];
    }
}

function getUserStats($pdo) {
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN type = 'client' THEN 1 ELSE 0 END) as clients,
            SUM(CASE WHEN type = 'professionnel' THEN 1 ELSE 0 END) as professionals
        FROM utilisateur");
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log('User stats error: ' . $e->getMessage());
        return ['total_users' => 0, 'clients' => 0, 'professionals' => 0];
    }
}

$stockStats = getStockStats($pdo);
$lowStockItems = getLowStockItems($pdo, 8);
$recentInvoices = getRecentInvoices($pdo, 10);
$invoiceStats = getInvoiceStats($pdo);
$promotions = getPromotions($pdo, 10);
$salesData = getSalesData($pdo);
$topProducts = getTopProducts($pdo, 5);
$userStats = getUserStats($pdo);

$totalRevenue = array_sum(array_column($salesData, 'total_sales'));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                        <span class="dash-nav-label">Vue d'ensemble</span>
                        <a href="#section-overview" class="dash-nav-link active" onclick="showSection('overview',this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Tableau de bord
                        </a>
                    </div>

                    <div class="dash-nav-group">
                        <span class="dash-nav-label">Gestion</span>
                        <a href="#section-clients" class="dash-nav-link" onclick="showSection('clients',this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Gestion des clients
                        </a>
                        <a href="#section-stock" class="dash-nav-link" onclick="showSection('stock',this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Gestion du stock
                        </a>
                        <a href="#section-achats" class="dash-nav-link" onclick="showSection('achats',this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Gestion des achats
                        </a>
                        <a href="#section-promos" class="dash-nav-link" onclick="showSection('promos',this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            Promotions & Réductions
                        </a>
                    </div>

                    <div class="dash-nav-group">
                        <span class="dash-nav-label">Compte</span>
                        <a href="profile.php" class="dash-nav-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            Mon profil
                        </a>
                        <a href="catalogue.php" class="dash-nav-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            Retour au catalogue
                        </a>
                        <a href="logout.php" class="dash-nav-link" style="color:#ef4444">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Déconnexion
                        </a>
                    </div>
                </nav>
            </div>
        </aside>

        <main class="dashboard-container">

            <div class="dashboard-grid" style="margin-bottom:32px">
                <div class="stat-card">
                    <div class="stat-icon stock">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </div>
                    <span class="stat-label">Stock Total</span>
                    <span class="stat-value"><?= number_format($stockStats['total_stock'] ?? 0, 0, ',', ' ') ?></span>
                    <span class="stat-subtext"><?= $stockStats['total_articles'] ?? 0 ?> articles</span>
                </div>

                <div class="stat-card">
                    <div class="stat-icon invoices">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                    <span class="stat-label">Factures</span>
                    <span class="stat-value"><?= $invoiceStats['total_invoices'] ?? 0 ?></span>
                    <span class="stat-subtext"><?= $invoiceStats['paid'] ?? 0 ?> payées</span>
                </div>

                <div class="stat-card">
                    <div class="stat-icon promotions">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9L18 3M6 9L6 21M18 3L18 15M6 9L18 15"></path>
                        </svg>
                    </div>
                    <span class="stat-label">Promotions Actives</span>
                    <span class="stat-value"><?= count($promotions) ?></span>
                    <span class="stat-subtext">Articles en solde</span>
                </div>

                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="1"></circle>
                            <path
                                d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m3.08 3.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m3.08-3.08l4.24-4.24">
                            </path>
                        </svg>
                    </div>
                    <span class="stat-label">Revenu (7j)</span>
                    <span class="stat-value"><?= number_format($totalRevenue ?? 0, 0, ',', ' ') ?> DA</span>
                    <span class="stat-subtext">derniers 7 jours</span>
                </div>
            </div>

            <div id="section-overview">
            <div class="dashboard-header">
                <div class="welcome-msg">
                    <h1>Tableau de bord</h1>
                    <p>Bienvenue, <?= htmlspecialchars($authName, ENT_QUOTES) ?> — gestion complète BibenQuinc</p>
                </div>
            </div>
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                            </svg>
                            Gestion du Stock
                        </h2>
                        <a href="catalogue.php" class="view-all">Voir tous →</a>
                    </div>
                    
                    <div class="stock-summary">
                        <div class="summary-item">
                            <span>Articles en stock</span>
                            <strong><?= number_format($stockStats['total_stock'] ?? 0, 0) ?></strong>
                        </div>
                        <div class="summary-item warning">
                            <span>Stock faible</span>
                            <strong><?= $stockStats['low_stock'] ?? 0 ?></strong>
                        </div>
                        <div class="summary-item danger">
                            <span>Rupture de stock</span>
                            <strong><?= $stockStats['out_of_stock'] ?? 0 ?></strong>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Catégorie</th>
                                    <th>Stock</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($lowStockItems)): ?>
                                    <?php foreach ($lowStockItems as $item): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($item['intitule']) ?></strong></td>
                                            <td><?= htmlspecialchars($item['intituleCat']) ?></td>
                                            <td><?= $item['nbDispo'] ?> unités</td>
                                            <td>
                                                <?php if ($item['nbDispo'] == 0): ?>
                                                    <span class="badge danger">Rupture</span>
                                                <?php elseif ($item['nbDispo'] < 5): ?>
                                                    <span class="badge alert">Critique</span>
                                                <?php else: ?>
                                                    <span class="badge warning">Faible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn-small" onclick="editStock(<?= $item['idArt'] ?>)">Modifier</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Tous les articles sont bien achalandisés</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            Gestion des Factures
                        </h2>
                        <a href="#" class="view-all">Rapport complet →</a>
                    </div>

                    <div class="invoice-summary">
                        <div class="summary-item">
                            <span>Total factures</span>
                            <strong><?= $invoiceStats['total_invoices'] ?? 0 ?></strong>
                        </div>
                        <div class="summary-item success">
                            <span>Payées</span>
                            <strong><?= $invoiceStats['paid'] ?? 0 ?></strong>
                        </div>
                        <div class="summary-item warning">
                            <span>En attente</span>
                            <strong><?= $invoiceStats['unpaid'] ?? 0 ?></strong>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>N° Facture</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentInvoices)): ?>
                                    <?php foreach ($recentInvoices as $invoice): ?>
                                        <tr>
                                            <td><strong>#<?= $invoice['idFac'] ?></strong></td>
                                            <td><?= htmlspecialchars($invoice['prenom'] . ' ' . $invoice['nom']) ?></td>
                                            <td><?= number_format($invoice['total'] ?? 0, 0, ',', ' ') ?> DA</td>
                                            <td><?= date('d/m/Y H:i', strtotime($invoice['dateFac'])) ?></td>
                                            <td>
                                                <?php if ($invoice['etat'] === 'payée'): ?>
                                                    <span class="badge success">Payée</span>
                                                <?php else: ?>
                                                    <span class="badge warning">Non payée</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn-small" onclick="viewInvoice(<?= $invoice['idFac'] ?>)">Voir</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Aucune facture trouvée</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9L18 3M6 9L6 21M18 3L18 15M6 9L18 15"></path>
                            </svg>
                            Promotions et Réductions Actives
                        </h2>
                        <a href="#" class="view-all">Ajouter promotion →</a>
                    </div>

                    <div class="promotions-grid">
                        <?php if (!empty($promotions)): ?>
                            <?php foreach (array_slice($promotions, 0, 6) as $promo): ?>
                                <div class="promo-card">
                                    <div class="promo-badge">-<?= $promo['discount_percent'] ?>%</div>
                                    <h3><?= htmlspecialchars($promo['intitule']) ?></h3>
                                    <p class="category"><?= htmlspecialchars($promo['intituleCat']) ?></p>
                                    <div class="promo-prices">
                                        <span class="old-price"><?= number_format($promo['oldPrice'] ?? 0, 0, ',', ' ') ?> DA</span>
                                        <span class="new-price"><?= number_format($promo['newPrice'] ?? 0, 0, ',', ' ') ?> DA</span>
                                    </div>
                                    <button class="btn-small" onclick="editPromo(<?= $promo['idArt'] ?>)">Modifier</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Aucune promotion active</p>
                                <button class="btn-primary">Créer une promotion</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if (!empty($salesData)): ?>
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                            Aperçu des Ventes (7 derniers jours)
                        </h2>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Commandes</th>
                                    <th>Ventes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesData as $sale): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($sale['date'])) ?></td>
                                        <td><?= $sale['orders'] ?></td>
                                        <td><?= number_format($sale['total_sales'] ?? 0, 0, ',', ' ') ?> DA</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty($topProducts)): ?>
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="12 1 3 5 3 19 12 23 21 19 21 5 12 1"></polyline>
                                <polyline points="12 12 3 8 12 4 21 8 12 12"></polyline>
                                <polyline points="12 12 12 23"></polyline>
                                <polyline points="3 8 12 14 21 8"></polyline>
                            </svg>
                            Produits les Plus Vendus
                        </h2>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Quantité Vendue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $idx => $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $idx + 1 ?>. <?= htmlspecialchars($product['intitule']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($product['intituleCat']) ?></td>
                                        <td><?= number_format($product['prix'] ?? 0, 0, ',', ' ') ?> DA</td>
                                        <td><?= $product['total_sold'] ?? 0 ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php endif; ?>

            </div>

            <div id="section-clients" style="display:none">
                <div class="dashboard-header"><h1>Gestion des clients</h1><p>Liste et gestion de tous les utilisateurs inscrits</p></div>
                <section class="dashboard-section">
                    <div class="section-header"><h2>Clients inscrits</h2></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>#</th><th>Nom</th><th>Email</th><th>Tél.</th><th>Type</th><th>Inscrit le</th></tr></thead>
                            <tbody>
                            <?php
                            try {
                                $stmtU = $pdo->query("SELECT idUtili, nom, prenom, email, numTel, type, created_at FROM utilisateur ORDER BY created_at DESC");
                                foreach ($stmtU->fetchAll() as $u): ?>
                                <tr>
                                    <td><strong>#<?= $u['idUtili'] ?></strong></td>
                                    <td><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= htmlspecialchars($u['numTel']) ?></td>
                                    <td><span class="badge <?= $u['type'] === 'professionnel' ? 'warning' : 'success' ?>"><?= htmlspecialchars($u['type']) ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                </tr>
                                <?php endforeach;
                            } catch(Exception $e) { echo '<tr><td colspan="6" class="text-center">Erreur de chargement</td></tr>'; } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div id="section-achats" style="display:none">
                <div class="dashboard-header"><h1>Gestion des achats</h1><p>Suivi des commandes et génération de factures</p></div>
                <section class="dashboard-section">
                    <div class="section-header"><h2>Commandes récentes</h2></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>#Achat</th><th>Client</th><th>Total</th><th>Date</th><th>Facture</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php
                            try {
                                $stmtA = $pdo->query("SELECT a.idAchat, a.total, a.dateAchat, u.nom, u.prenom, f.idFac, f.etat FROM achat a JOIN utilisateur u ON a.idClient = u.idUtili LEFT JOIN facture f ON f.idAchat = a.idAchat ORDER BY a.dateAchat DESC LIMIT 20");
                                foreach ($stmtA->fetchAll() as $achat): ?>
                                <tr>
                                    <td><strong>#<?= $achat['idAchat'] ?></strong></td>
                                    <td><?= htmlspecialchars($achat['prenom'] . ' ' . $achat['nom']) ?></td>
                                    <td><?= number_format($achat['total'], 0, ',', ' ') ?> DA</td>
                                    <td><?= date('d/m/Y H:i', strtotime($achat['dateAchat'])) ?></td>
                                    <td><?php if ($achat['idFac']): ?><span class="badge <?= $achat['etat'] === 'payée' ? 'success' : 'warning' ?>"><?= htmlspecialchars($achat['etat']) ?></span><?php else: ?><span class="badge alert">Non générée</span><?php endif; ?></td>
                                    <td>
                                        <?php if (!$achat['idFac']): ?>
                                        <form method="post" action="" style="display:inline">
                                            <input type="hidden" name="generate_facture" value="<?= $achat['idAchat'] ?>">
                                            <button type="submit" class="btn-small">Générer facture</button>
                                        </form>
                                        <?php else: ?>
                                        <button class="btn-small" onclick="viewInvoice(<?= $achat['idFac'] ?>)">Voir</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach;
                            } catch(Exception $e) { echo '<tr><td colspan="6" class="text-center">Erreur de chargement</td></tr>'; } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- ── Promotions ── -->
            <div id="section-promos" style="display:none">
                <div class="dashboard-header"><h1>Promotions &amp; Réductions</h1><p>Gérez les offres et remises sur les articles</p></div>
                <section class="dashboard-section">
                    <div class="section-header"><h2>Promotions actives</h2><button class="btn-small" onclick="document.getElementById('add-promo-form').classList.toggle('hidden')">+ Ajouter</button></div>
                    <div id="add-promo-form" class="hidden" style="background:var(--bg);border-radius:8px;padding:20px;margin-bottom:20px;border:1px solid var(--border)">
                        <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end">
                            <div><label style="font-size:12px;font-weight:600;color:var(--text-muted)">Article (ID)</label><input type="number" name="promo_idart" placeholder="ID article" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text)"></div>
                            <div><label style="font-size:12px;font-weight:600;color:var(--text-muted)">Ancien prix (DA)</label><input type="number" name="promo_old" placeholder="Ex: 5000" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text)"></div>
                            <div><label style="font-size:12px;font-weight:600;color:var(--text-muted)">Nouveau prix (DA)</label><input type="number" name="promo_new" placeholder="Ex: 3500" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text)"></div>
                            <button type="submit" name="save_promo" class="btn-small">Enregistrer</button>
                        </form>
                    </div>
                    <div class="promotions-grid">
                    <?php foreach ($promotions as $promo): ?>
                        <div class="promo-card">
                            <div class="promo-badge">-<?= $promo['discount_percent'] ?>%</div>
                            <h3><?= htmlspecialchars($promo['intitule']) ?></h3>
                            <p class="category"><?= htmlspecialchars($promo['intituleCat']) ?></p>
                            <div class="promo-prices">
                                <span class="old-price"><?= number_format($promo['oldPrice'], 0, ',', ' ') ?> DA</span>
                                <span class="new-price"><?= number_format($promo['newPrice'], 0, ',', ' ') ?> DA</span>
                            </div>
                            <button class="btn-small" onclick="editPromo(<?= $promo['idArt'] ?>)">Modifier</button>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($promotions)): ?><div class="empty-state"><p>Aucune promotion active</p></div><?php endif; ?>
                    </div>
                </section>
            </div>

        </main>
        </div><!-- /dash-layout -->

        <footer class="site-footer">
            <p>© 2026 BibenQuinc - Tous droits réservés</p>
        </footer>
    </div>

    <style>
        /* ── Dash Layout ── */
        
    </style>

    <script>
        const SECTIONS = ['overview','clients','stock','achats','promos'];

        function showSection(id, linkEl) {
            SECTIONS.forEach(s => {
                const el = document.getElementById('section-' + s);
                if (el) el.style.display = s === id ? '' : 'none';
            });
            document.querySelectorAll('.dash-nav-link').forEach(l => l.classList.remove('active'));
            if (linkEl) linkEl.classList.add('active');
            history.replaceState(null, '', '?section=' + id);
        }

        // Auto-activate section from URL param on load
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(location.search);
            const sec = params.get('section');
            if (sec && SECTIONS.includes(sec)) {
                const link = document.querySelector('.dash-nav-link[onclick*="\'' + sec + '\'"]');
                showSection(sec, link);
            }
        });

        function toggleUserMenu(e) {
            e.preventDefault();
            const dropdown = document.getElementById('userDropdown');
            const btn = document.getElementById('userMenuBtn');
            dropdown.classList.toggle('open');
            btn.classList.toggle('active');
        }

        document.addEventListener('click', function (e) {
            const userMenu = document.querySelector('.user-menu');
            if (userMenu && !userMenu.contains(e.target)) {
                document.getElementById('userDropdown')?.classList.remove('open');
                document.getElementById('userMenuBtn')?.classList.remove('active');
            }
        });

        function editStock(productId) {
            const qty = prompt('Nouveau stock pour le produit #' + productId + ' :');
            if (qty !== null && !isNaN(parseInt(qty))) {
                fetch('', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'update_stock=1&idArt=' + productId + '&qty=' + parseInt(qty) })
                    .then(() => location.reload());
            }
        }

        function openFacture(idFac, idAchat, client, total, date, etat) {
            document.getElementById('fac-id').textContent    = 'FAC-' + String(idFac).padStart(4,'0');
            document.getElementById('fac-order').textContent = 'BQ-2026-' + String(idAchat).padStart(4,'0');
            document.getElementById('fac-client').textContent = client;
            document.getElementById('fac-total').textContent  = total + ' DA';
            document.getElementById('fac-date').textContent   = date;
            const badge = document.getElementById('fac-etat');
            badge.textContent = etat === 'payée' ? 'Payée' : 'Non payée';
            badge.className   = 'badge ' + (etat === 'payée' ? 'success' : 'warning');

            // Load article details via AJAX
            fetch('facture_detail.php?idAchat=' + idAchat)
                .then(r => r.json())
                .then(items => {
                    const cont = document.getElementById('fac-items');
                    if (!items.length) { cont.innerHTML = '<p style="color:var(--text-muted)">Aucun article trouvé.</p>'; return; }
                    cont.innerHTML = items.map(i => `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
                            <div>
                                <div style="font-weight:600;font-size:14px">${i.intitule}</div>
                                <div style="font-size:12px;color:var(--text-muted)">Qté : ${i.quantite} × ${Number(i.prix).toLocaleString('fr-DZ')} DA</div>
                            </div>
                            <div style="font-weight:700;color:var(--accent)">${(i.quantite * i.prix).toLocaleString('fr-DZ')} DA</div>
                        </div>`).join('');
                })
                .catch(() => {
                    document.getElementById('fac-items').innerHTML = '<p style="color:var(--text-muted)">Erreur de chargement.</p>';
                });

            document.getElementById('factureModal').classList.add('open');
        }

        function closeFacture() {
            document.getElementById('factureModal').classList.remove('open');
        }

        function editPromo(productId) {
            alert('Modification de la promotion pour le produit #' + productId);
        }
    </script>

<!-- ══ Facture Modal ══ -->
<div id="factureModal" style="display:none" onclick="if(event.target===this)closeFacture()" class="fac-modal-bg">
</div>
<div id="factureModalPanel" class="fac-modal-panel">
    <div class="fac-modal-head">
        <div>
            <h2 style="margin:0;font-size:18px">Facture <span id="fac-id"></span></h2>
            <div style="font-size:13px;color:var(--text-muted);margin-top:4px">Commande <span id="fac-order"></span></div>
        </div>
        <button onclick="closeFacture()" class="close-btn" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text)">✕</button>
    </div>
    <div class="fac-modal-body">
        <div class="fac-info-grid">
            <div><span class="fac-label">Client</span><span id="fac-client" class="fac-value"></span></div>
            <div><span class="fac-label">Date</span><span id="fac-date" class="fac-value"></span></div>
            <div><span class="fac-label">Statut</span><span id="fac-etat" class="badge"></span></div>
            <div><span class="fac-label">Total</span><span id="fac-total" class="fac-value" style="color:var(--accent);font-size:18px;font-weight:800"></span></div>
        </div>
        <h3 style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin:20px 0 10px">Articles commandés</h3>
        <div id="fac-items"></div>
    </div>
</div>

<style>
.fac-modal-bg {
    position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;
    backdrop-filter:blur(4px);
    opacity:0;visibility:hidden;transition:all .3s;
}
.fac-modal-bg.open { opacity:1;visibility:visible; }
.fac-modal-bg.open ~ #factureModalPanel { transform:translateY(0); }
#factureModalPanel {
    position:fixed;top:50%;left:50%;transform:translate(-50%,-40%);
    width:520px;max-width:95vw;max-height:85vh;
    background:var(--surface);border-radius:16px;
    box-shadow:0 24px 80px rgba(0,0,0,.35);
    z-index:2001;overflow:hidden;display:flex;flex-direction:column;
    transition:transform .3s cubic-bezier(.4,0,.2,1);
}
.fac-modal-bg.open + #factureModalPanel { transform:translate(-50%,-50%); }
.fac-modal-head {
    display:flex;justify-content:space-between;align-items:flex-start;
    padding:20px 24px;border-bottom:1px solid var(--border);flex-shrink:0;
}
.fac-modal-body { padding:24px;overflow-y:auto;flex:1; }
.fac-info-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px;background:var(--bg);border-radius:10px;padding:16px;border:1px solid var(--border); }
.fac-label { display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:4px; }
.fac-value { font-size:14px;font-weight:600;color:var(--text); }
</style>

<script>
// Sync modal backdrop open state with panel
document.getElementById('factureModal').addEventListener('transitionend', function() {
    const panel = document.getElementById('factureModalPanel');
    if (this.classList.contains('open')) panel.style.display = 'flex';
});
document.getElementById('factureModal').style.display = '';
</script>

</body>

</html>
