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

// Get data for dashboard
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

            <nav class="nav">
                <a href="catalogue.php">Catalogue</a>
                <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
                <a href="contact.php">Contact</a>
                <?php endif; ?>
                <div class="user-menu">
                    <button class="user-btn" id="userMenuBtn" onclick="toggleUserMenu(event)" style="background-color: var(--bg, #f8fafc); border-radius: 8px;">
                        <?= htmlspecialchars((string) $authName, ENT_QUOTES) ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="dashboard.php">Tableau de bord</a>
                        <a href="profile.php">Mon profil</a>
                        <a href="logout.php">Déconnexion</a>
                    </div>
                </div>
                <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
                <a href="#" class="cart-btn" onclick="return false;">
                    Mon panier
                </a>
                <?php endif; ?>
            </nav>
        </header>

        <main class="dashboard-container">
            <div class="dashboard-header">
                <div class="welcome-msg">
                    <h1>Tableau de bord - BibenQuinc</h1>
                    <p>Gestion du stock, des factures et des promotions</p>
                </div>
            </div>

            <!-- Key Statistics Cards -->
            <div class="dashboard-grid">
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

            <!-- Main Content Grid -->
            <div class="dashboard-content">

                <!-- Stock Management Section -->
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

                <!-- Invoices Section -->
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

                <!-- Promotions Section -->
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

                <!-- Sales Overview Section -->
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

                <!-- Top Products Section -->
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

        </main>

        <footer class="site-footer">
            <p>© 2026 BibenQuinc - Tous droits réservés</p>
        </footer>
    </div>

    <style>
        /* Dashboard Container */
        .dashboard-container {
            padding: 32px 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .dashboard-header p {
            color: var(--text-muted);
            font-size: 16px;
        }

        /* Statistics Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all var(--t);
        }

        .stat-card:hover {
            border-color: var(--accent);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-icon.stock { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-icon.invoices { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.promotions { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.revenue { background: linear-gradient(135deg, #e85d04, #c44d00); }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text);
        }

        .stat-subtext {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Dashboard Content */
        .dashboard-content {
            display: grid;
            gap: 32px;
        }

        .dashboard-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 28px;
            overflow-x: auto;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .section-header svg {
            color: var(--accent);
        }

        .view-all {
            color: var(--accent);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all var(--t);
        }

        .view-all:hover {
            gap: 8px;
        }

        /* Summary Cards */
        .stock-summary,
        .invoice-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-item {
            background: var(--bg);
            padding: 16px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-left: 4px solid var(--accent);
        }

        .summary-item span {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .summary-item strong {
            font-size: 24px;
            color: var(--text);
            font-weight: 700;
        }

        .summary-item.success { border-left-color: #10b981; }
        .summary-item.warning { border-left-color: #f59e0b; }
        .summary-item.danger { border-left-color: #ef4444; }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table thead {
            background: var(--bg);
            border-bottom: 2px solid var(--border);
        }

        .data-table th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            color: var(--text);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }

        .data-table tbody tr:hover {
            background: var(--bg);
        }

        .data-table .text-center {
            text-align: center;
            color: var(--text-muted);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.alert {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.danger {
            background: #fecaca;
            color: #7f1d1d;
        }

        /* Promotions Grid */
        .promotions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .promo-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--r-md);
            padding: 16px;
            position: relative;
            transition: all var(--t);
            display: flex;
            flex-direction: column;
        }

        .promo-card:hover {
            border-color: var(--accent);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        .promo-badge {
            position: absolute;
            top: -12px;
            right: 12px;
            background: var(--accent);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .promo-card h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            margin: 16px 0 4px 0;
        }

        .promo-card .category {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .promo-prices {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 12px;
            flex-grow: 1;
        }

        .old-price {
            font-size: 12px;
            color: var(--text-muted);
            text-decoration: line-through;
        }

        .new-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
        }

        /* Buttons */
        .btn-small {
            padding: 8px 12px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--t);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-small:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(232, 93, 4, 0.2);
        }

        .btn-primary {
            padding: 12px 24px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--r-md);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--t);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px 16px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header h1 {
                font-size: 24px;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 12px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .promotions-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }
        }

        body.elec .stat-card {
            border-color: var(--border);
        }

        body.elec .data-table thead {
            background: var(--bg);
        }

        body.elec .section-header {
            border-bottom-color: var(--border);
        }
    </style>

    <script>
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
                document.getElementById('userDropdown').classList.remove('open');
                document.getElementById('userMenuBtn').classList.remove('active');
            }
        });

        function editStock(productId) {
            alert('Modifier le stock du produit #' + productId);
            // Implement stock editing functionality
        }

        function viewInvoice(invoiceId) {
            alert('Voir la facture #' + invoiceId);
            // Implement invoice viewing functionality
        }

        function editPromo(productId) {
            alert('Modifier la promotion du produit #' + productId);
            // Implement promotion editing functionality
        }
    </script>

</body>

</html>

