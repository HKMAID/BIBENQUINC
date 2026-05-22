<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/db.php';

initSession();
$authUser = currentUser();

if (!$authUser) {
    header('Location: cnx.php');
    exit;
}

if (($authUser['type'] ?? 'client') !== 'professionnel') {
    setFlash('error', 'Accès non autorisé. Réservé aux professionnels.');
    header('Location: catalogue.php');
    exit;
}

$authName = trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? ''));
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');

$pdo      = getPDO();
$userType = $authUser['type'] ?? 'client';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_facture'])) {
        $idAchat = (int) $_POST['generate_facture'];
        // Guard : vérifier que l'achat existe et n'a pas déjà de facture
        $existing = $pdo->prepare('SELECT idFac FROM facture WHERE idAchat = ? LIMIT 1');
        $existing->execute([$idAchat]);
        if (!$existing->fetch()) {
            $pdo->prepare("INSERT INTO facture (idAchat, etat, dateFac) VALUES (?, 'non payée', NOW())")
                ->execute([$idAchat]);
        }
        header('Location: dashboard.php?section=achats');
        exit;
    }
    if (isset($_POST['mark_paid'])) {
        $idFac = (int) $_POST['mark_paid'];
        // Guard : s'assurer que la facture existe et est bien non payée
        $pdo->prepare("UPDATE facture SET etat='payée' WHERE idFac=? AND etat='non payée'")
            ->execute([$idFac]);
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
    if (isset($_POST['add_article'])) {
        $intitule = trim($_POST['intitule'] ?? '');
        $idCat    = (int)($_POST['idCat']   ?? 0);
        $prix     = (int)($_POST['prix']     ?? 0);
        $nbDispo  = max(0, (int)($_POST['nbDispo'] ?? 0));
        $marque   = trim($_POST['marque']   ?? '');
        $photo    = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','gif'];
            if (in_array($ext, $allowed)) {
                $fname = 'art_' . uniqid() . '.' . $ext;
                $dest  = __DIR__ . '/img/' . $fname;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    $photo = 'img/' . $fname;
                }
            }
        }
        if ($intitule !== '' && $idCat > 0 && $prix > 0 && $marque !== '') {
            $pdo->prepare("INSERT INTO article (idCat, intitule, prix, nbDispo, marque, photo) VALUES (?,?,?,?,?,?)")
                ->execute([$idCat, $intitule, $prix, $nbDispo, $marque, $photo]);
        }
        header('Location: dashboard.php?section=stock');
        exit;
    }
}

function formatCategoryName(string $cat): string {
    $map = [
        'outillage' => 'Outillage', 'visserie&boulonnerie' => 'Visserie & Boulonnerie',
        'plomberie' => 'Plomberie', 'peinture' => 'Peinture', 'jardinage' => 'Jardinage',
        'serrurerie' => 'Serrurerie', 'ampoules&eclairage' => 'Ampoules & Éclairage',
        'cables&fils' => 'Câbles & Fils', 'disjoncteurs' => 'Disjoncteurs',
        'prises&interrupteurs' => 'Prises & Interrupteurs', 'projecteurs' => 'Projecteurs',
        'comptage' => 'Comptage'
    ];
    return $map[$cat] ?? ucfirst($cat);
}

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
            SELECT a.idArt, a.intitule, a.marque, a.nbDispo, c.intituleCat, a.prix
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
            SELECT p.idArt, a.intitule, a.prix, a.photo, p.oldPrice, p.newPrice,
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

$stockStats    = getStockStats($pdo);
$allStockItems = getAllStockItems($pdo);
$recentInvoices= getRecentInvoices($pdo, 10);
$invoiceStats  = getInvoiceStats($pdo);
$promotions    = getPromotions($pdo, 10);
$salesData     = getSalesData($pdo);
$topProducts   = getTopProducts($pdo, 5);
$userStats     = getUserStats($pdo);
$totalRevenue  = array_sum(array_column($salesData, 'total_sales'));
try {
    $categories = $pdo->query("SELECT idCat, intituleCat FROM categorie ORDER BY intituleCat")->fetchAll();
} catch (Exception $e) { $categories = []; }
try {
    $allArticles = $pdo->query("SELECT idArt, intitule FROM article ORDER BY intitule")->fetchAll();
} catch (Exception $e) { $allArticles = []; }

$pageTitle = 'Tableau de bord — BibenQuinc';
$activeNav = 'dashboard';
$extraCss  = ['views/style/dashboard.css'];
include __DIR__ . '/includes/header.php';
?>

    <div class="dash-layout">
        <aside class="dash-sidebar" id="dashSidebar">
            <div class="dash-sidebar-inner">

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
                </nav>

            </div>
        </aside>

        <main class="dashboard-main">
        <div class="dashboard-container">
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
                    <div class="section-toolbar">
                        <div class="search-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="search" class="toolbar-search" placeholder="Rechercher nom, email…" oninput="filterTable('section-clients', this.value, [1,2])">
                        </div>
                        <select class="toolbar-sort" onchange="sortTableBy('section-clients', this.value)">
                            <option value="">Trier par…</option>
                            <option value="1:str:asc">Nom A→Z</option>
                            <option value="1:str:desc">Nom Z→A</option>
                            <option value="4:str:desc">Inscription (récent)</option>
                            <option value="4:str:asc">Inscription (ancien)</option>
                        </select>
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
                        <button class="btn-small" onclick="toggleAddArticle()">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Ajouter un article
                        </button>
                    </div>

                    <form method="post" enctype="multipart/form-data" id="add-article-form" class="add-article-form hidden">
                        <div class="form-grid">
                            <div>
                                <label for="art-intitule">Intitulé *</label>
                                <input id="art-intitule" type="text" name="intitule" class="form-input" placeholder="Nom de l'article" maxlength="200" required>
                            </div>
                            <div>
                                <label for="art-marque">Marque *</label>
                                <input id="art-marque" type="text" name="marque" class="form-input" placeholder="Ex : Bosch" maxlength="70" required>
                            </div>
                            <div>
                                <label for="art-cat">Catégorie *</label>
                                <select id="art-cat" name="idCat" class="form-input" required>
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['idCat'] ?>"><?= htmlspecialchars(formatCategoryName($cat['intituleCat'])) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="art-prix">Prix (DA) *</label>
                                <input id="art-prix" type="number" name="prix" class="form-input" placeholder="Ex : 5000" min="1" required>
                            </div>
                            <div>
                                <label for="art-stock">Stock initial</label>
                                <input id="art-stock" type="number" name="nbDispo" class="form-input" placeholder="0" min="0" value="0">
                            </div>
                            <div>
                                <label>Photo (jpg, png, webp)</label>
                                <div class="photo-preview-wrap">
                                    <input type="file" name="photo" class="form-input" accept=".jpg,.jpeg,.png,.webp,.gif" onchange="previewPhoto(this)" style="flex:1">
                                    <img id="photo-preview" src="" alt="Aperçu">
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add_article" class="btn-small">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Enregistrer
                            </button>
                            <button type="button" class="btn-ghost" onclick="toggleAddArticle()">Annuler</button>
                        </div>
                    </form>

                    <div class="section-toolbar">
                        <div class="search-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="search" class="toolbar-search" placeholder="Rechercher article, marque…" oninput="filterTable('section-stock', this.value, [0,1,2])">
                        </div>
                        <select class="toolbar-sort" onchange="sortTableBy('section-stock', this.value)">
                            <option value="">Trier par…</option>
                            <option value="0:str:asc">Article A→Z</option>
                            <option value="0:str:desc">Article Z→A</option>
                            <option value="3:num:asc">Prix ↑</option>
                            <option value="3:num:desc">Prix ↓</option>
                            <option value="4:num:asc">Stock ↑</option>
                            <option value="4:num:desc">Stock ↓</option>
                        </select>
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
                                    <th>Marque</th>
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
                                    <td><?= htmlspecialchars($item['marque']) ?></td>
                                    <td><?= htmlspecialchars(formatCategoryName($item['intituleCat'])) ?></td>
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
                    <div class="section-toolbar">
                        <div class="search-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="search" class="toolbar-search" placeholder="Rechercher référence, client…" oninput="filterTable('section-achats', this.value, [0,1])">
                        </div>
                        <select class="toolbar-sort" onchange="sortTableBy('section-achats', this.value)">
                            <option value="">Trier par…</option>
                            <option value="4:str:desc">Date (récent)</option>
                            <option value="4:str:asc">Date (ancien)</option>
                            <option value="3:num:desc">Total ↓</option>
                            <option value="3:num:asc">Total ↑</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#Achat</th>
                                    <th>Client</th>
                                    <th>Articles</th>
                                    <th>Total</th>
                                    <th>Livraison</th>
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
                                           a.modeLivraison, a.wilaya,
                                           u.nom, u.prenom, u.email,
                                           f.idFac, f.etat, f.dateFac,
                                           COUNT(aa.idArticle) as nb_articles
                                    FROM achat a
                                    JOIN utilisateur u      ON a.idClient  = u.idUtili
                                    LEFT JOIN facture f     ON f.idAchat   = a.idAchat
                                    LEFT JOIN article_achat aa ON aa.idAchat = a.idAchat
                                    GROUP BY a.idAchat, a.total, a.dateAchat,
                                             a.modeLivraison, a.wilaya,
                                             u.nom, u.prenom, u.email,
                                             f.idFac, f.etat, f.dateFac
                                    ORDER BY a.dateAchat DESC
                                    LIMIT 30");
                                $achats = $stmtA->fetchAll();

                                if (empty($achats)) {
                                    echo '<tr><td colspan="9" class="text-center">Aucune commande</td></tr>';
                                } else {
                                    foreach ($achats as $achat):
                                        $orderRef = 'BQ-' . date('Y', strtotime($achat['dateAchat'])) . '-' . str_pad((string)$achat['idAchat'], 4, '0', STR_PAD_LEFT);
                                        $clientName = htmlspecialchars($achat['prenom'] . ' ' . $achat['nom']);
                                        $totalFmt   = number_format((float)$achat['total'], 0, ',', ' ');
                                        $dateFmt    = date('d/m/Y H:i', strtotime($achat['dateAchat']));
                                        $modeLabel  = match($achat['modeLivraison'] ?? '') {
                                            'home'      => '🏠 Domicile',
                                            'zrexpress' => '📦 ZrExpress',
                                            default     => '—'
                                        };
                                        $wilayaLabel = !empty($achat['wilaya']) ? ' (W' . htmlspecialchars($achat['wilaya']) . ')' : '';
                                ?>
                                <tr>
                                    <td><strong><?= $orderRef ?></strong></td>
                                    <td>
                                        <div><strong><?= $clientName ?></strong></div>
                                        <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($achat['email']) ?></div>
                                    </td>
                                    <td><?= (int)$achat['nb_articles'] ?> réf.</td>
                                    <td><strong><?= $totalFmt ?> DA</strong></td>
                                    <td style="white-space:nowrap"><?= $modeLabel . $wilayaLabel ?></td>
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
                                                <button type="submit" class="btn-small">Créer</button>
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
                                echo '<tr><td colspan="9" class="text-center">Erreur : ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

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
                    <div class="section-toolbar" style="margin-bottom:0;margin-top:4px">
                        <div class="search-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="search" class="toolbar-search" placeholder="Rechercher article, catégorie…" oninput="filterPromos(this.value)">
                        </div>
                    </div>

                    <form method="post" id="add-promo-form" class="add-promo-form hidden">
                        <div>
                            <label for="promo_idart">Article *</label>
                            <select id="promo_idart" name="promo_idart" class="form-input" required>
                                <option value="">— Sélectionner un article —</option>
                                <?php foreach ($allArticles as $art): ?>
                                <option value="<?= (int)$art['idArt'] ?>"><?= htmlspecialchars($art['intitule']) ?></option>
                                <?php endforeach; ?>
                            </select>
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
                        <?php foreach ($promotions as $promo):
                            $promoImg = !empty($promo['photo']) ? htmlspecialchars($promo['photo']) : 'img/tournevis.svg';
                        ?>
                        <div class="promo-card">
                            <div class="promo-badge">-<?= (int)$promo['discount_percent'] ?>%</div>
                            <div class="promo-img-wrap">
                                <img src="<?= $promoImg ?>" alt="<?= htmlspecialchars($promo['intitule']) ?>" class="promo-img" loading="lazy">
                            </div>
                            <h3><?= htmlspecialchars($promo['intitule']) ?></h3>
                            <p class="category"><?= htmlspecialchars(formatCategoryName($promo['intituleCat'])) ?></p>
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
        </div>
        <footer class="site-footer">
            <p>&copy; 2026 BibenQuinc &mdash; Tous droits réservés</p>
        </footer>
        </main>
    </div>

<div id="facture-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="fac-modal-title">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="fac-modal-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Détail de la facture
            </h3>
            <button class="modal-close" onclick="closeFactureModal()" aria-label="Fermer">x</button>
        </div>
        <div class="modal-body">
            <div class="fac-grid">
                <div class="fac-row"><span class="fac-label">N° Facture</span><strong id="fac-ref" class="fac-value accent">—</strong></div>
                <div class="fac-row"><span class="fac-label">Commande</span><span id="fac-achat" class="fac-value">—</span></div>
                <div class="fac-row"><span class="fac-label">Client</span><span id="fac-client" class="fac-value">—</span></div>
                <div class="fac-row"><span class="fac-label">Montant total</span><strong id="fac-total" class="fac-value">—</strong></div>
                <div class="fac-row"><span class="fac-label">Date facture</span><span id="fac-date" class="fac-value">—</span></div>
                <div class="fac-row"><span class="fac-label">Statut</span><span id="fac-etat" class="badge warning">—</span></div>
            </div>
            <div class="modal-actions">
                <a id="fac-download-btn" href="#" class="btn-small" style="text-decoration:none">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Télécharger (.txt)
                </a>
                <div id="fac-pay-form" style="display:none">
                    <form method="post">
                        <input type="hidden" name="mark_paid" id="fac-pay-input" value="">
                        <button type="submit" class="btn-small success-btn" style="width:100%">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Marquer comme payée
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="views/script/dashboard.js"></script>
</body>
</html>