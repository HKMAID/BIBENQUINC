<?php
require_once __DIR__ . '/includes/session.php';

initSession();
$authUser = currentUser();

if (!$authUser) {
    header('Location: cnx.php');
    exit;
}

$authName = trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? ''));
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');

// Mock data for dashboard
$stats = [
    'orders' => 12,
    'favorites' => 5,
    'points' => 350,
    'saved_carts' => 2
];

$activities = [
    ['msg' => 'Commande #3452 confirmée', 'time' => 'Il y a 2 heures'],
    ['msg' => 'Nouvel article ajouté aux favoris : Perceuse Bosch', 'time' => 'Hier'],
    ['msg' => 'Votre profil a été mis à jour', 'time' => 'Il y a 3 jours'],
    ['msg' => 'Bienvenue sur votre nouveau tableau de bord !', 'time' => 'Il y a 1 semaine'],
];
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
                <a href="contact.php">Contact</a>
                <div class="user-menu">
                    <button class="user-btn" id="userMenuBtn" onclick="toggleUserMenu(event)">
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
                <a href="#" class="cart-btn" onclick="return false;">
                    Mon panier
                </a>
            </nav>
        </header>

        <main class="dashboard-container">
            <div class="dashboard-header">
                <div class="welcome-msg">
                    <h1>Bonjour, <?= htmlspecialchars($authUser['firstname'] ?? 'Utilisateur') ?> !</h1>
                    <p>Heureux de vous revoir. Voici un aperçu de votre activité.</p>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </div>
                    <span class="stat-label">Gestion de stock</span>
                    <span class="stat-value"><?= $stats['orders'] ?></span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l9.52-9.52 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                            </path>
                        </svg>
                    </div>
                    <span class="stat-label">Gestion des factures</span>
                    <span class="stat-value"><?= $stats['favorites'] ?></span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12">
                            </path>
                        </svg>
                    </div>
                    <span class="stat-label">Promotions et soldes</span>
                    <span class="stat-value"><?= $stats['saved_carts'] ?></span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon
                                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                            </polygon>
                        </svg>
                    </div>
                    <span class="stat-label">Points Fidélité</span>
                    <span class="stat-value"><?= $stats['points'] ?> pts</span>
                </div>
            </div>

            <div class="dashboard-sections">
                <div class="section-card">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Activité récente
                    </h2>
                    <div class="activity-list">
                        <?php foreach ($activities as $act): ?>
                            <div class="activity-item">
                                <div class="activity-dot"></div>
                                <div class="activity-content">
                                    <p><?= htmlspecialchars($act['msg']) ?></p>
                                    <span class="activity-time"><?= htmlspecialchars($act['time']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section-card">
                    <h2>Raccourcis</h2>
                    <div class="quick-links">
                        <a href="catalogue.php" class="quick-link-btn">
                            <span>Catalogue Produits</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <a href="profile.php" class="quick-link-btn">
                            <span>Editer mon profil</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <a href="contact.php" class="quick-link-btn">
                            <span>Aide & Support</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <footer class="site-footer">
            <p>© 2026 BibenQuinc - Tous droits réservés</p>
        </footer>
    </div>

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
    </script>

</body>

</html>

