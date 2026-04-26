<?php
require_once __DIR__ . '/includes/session.php';

initSession();
$authUser = currentUser();
$authName = $authUser ? trim(($authUser['firstname'] ?? '') . ' ' . ($authUser['lastname'] ?? '')) : '';
$authName = $authName !== '' ? $authName : ($authUser['email'] ?? '');

if (!$authUser) {
    header('Location: cnx.php');
    exit;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil - BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="catalogue.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .profile-page {
            flex: 1;
        }
        .profile-header {
            background: var(--header-bg);
            padding: 24px 32px;
            border-bottom: 1px solid var(--border);
        }
        .profile-header h1 {
            font-family: var(--fd);
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }
        .profile-header p {
            color: var(--text-muted);
            font-size: 14px;
        }
        .profile-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 24px;
            margin: 24px 32px;
            max-width: 600px;
        }
        .profile-card h2 {
            font-family: var(--fd);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
        }
        .profile-field {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        .profile-field:last-child {
            border-bottom: none;
        }
        .profile-label {
            font-weight: 500;
            color: var(--text-muted);
        }
        .profile-value {
            color: var(--text);
            font-weight: 500;
        }
        .profile-actions {
            margin: 24px 32px;
            max-width: 600px;
        }
        .btn-edit {
            display: inline-block;
            padding: 10px 20px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: var(--r-md);
            font-weight: 500;
            transition: all var(--t);
        }
        .btn-edit:hover {
            background: var(--accent-dark);
        }
    </style>
</head>
<body>

<?php if ($flash): ?>
  <div style="position:fixed;top:74px;left:50%;transform:translateX(-50%);z-index:9999;background:#111827;color:#fff;padding:10px 14px;border-radius:8px;font-family:var(--fb);font-size:13px;">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
  </div>
<?php endif; ?>

<div class="app">

  <header class="site-header">
    <div class="logo">
      <span class="logo-bar"></span>
      <span class="logo-bolt"> </span>
      <span class="logo-image">
        <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word">
      </span>
    </div>

    <nav class="nav">
      <a href="catalogue.php">Catalogue</a>
      <a href="contact.php">Contact</a>
      <?php if ($authUser): ?>
        <div class="user-menu">
          <button class="user-btn" id="userMenuBtn" onclick="toggleUserMenu(event)">
            <?= htmlspecialchars((string) $authName, ENT_QUOTES) ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div class="user-dropdown" id="userDropdown">
            <a href="profile.php">Mon profil</a>
            <a href="pref.php">Mes préférences</a>
            <a href="logout.php">Déconnexion</a>
          </div>
        </div>
      <?php else: ?>
        <a href="cnx.php">Connexion</a>
      <?php endif; ?>
      <a href="#" class="cart-btn" onclick="openDrawer(); return false;">
        Mon panier
        <span class="count-chip" id="chipCount">0</span>
      </a>
    </nav>
  </header>

  <main class="main-content profile-page" style="max-width: 1400px; margin: 0 auto; padding: 28px 32px;">
    <div class="profile-header">
      <h1>Mon profil</h1>
      <p>Gérez vos informations personnelles</p>
    </div>

    <div class="profile-card">
      <h2>Informations du compte</h2>
      
      <div class="profile-field">
        <span class="profile-label">Nom complet</span>
        <span class="profile-value"><?= htmlspecialchars($authName) ?></span>
      </div>
      
      <div class="profile-field">
        <span class="profile-label">Email</span>
        <span class="profile-value"><?= htmlspecialchars($authUser['email'] ?? '') ?></span>
      </div>
      
      <div class="profile-field">
        <span class="profile-label">Prénom</span>
        <span class="profile-value"><?= htmlspecialchars($authUser['firstname'] ?? 'Non défini') ?></span>
      </div>
      
      <div class="profile-field">
        <span class="profile-label">Nom</span>
        <span class="profile-value"><?= htmlspecialchars($authUser['lastname'] ?? 'Non défini') ?></span>
      </div>
      
      <div class="profile-field">
        <span class="profile-label">Téléphone</span>
        <span class="profile-value"><?= htmlspecialchars($authUser['phone'] ?? 'Non défini') ?></span>
      </div>
      
      <div class="profile-field">
        <span class="profile-label">Adresse</span>
        <span class="profile-value"><?= htmlspecialchars($authUser['address'] ?? 'Non définie') ?></span>
      </div>
    </div>

    <div class="profile-actions">
      <a href="#" class="btn-edit">Modifier mes informations</a>
    </div>

  </main>

  <footer class="site-footer">
    <p>© 2026 BibenQuinc - Tous droits réservés</p>
  </footer>

</div>

<!-- Overlay + Cart Drawer -->
<div class="drawer-overlay" id="overlay" onclick="closeDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="drawer-head">
    <h2>Mon panier <span class="count-chip" id="chipCountDrawer">0</span></h2>
    <button class="close-btn" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-items" id="drawerItems">
    <div class="drawer-empty">Votre panier est vide</div>
  </div>
  <div class="drawer-footer">
    <div class="total-row">
      <span class="total-label">Total</span>
      <span class="total-amount" id="cartTotal">0 <span>DA</span></span>
    </div>
    <button class="checkout-btn" onclick="alert('Fonctionnalité de paiement à venir')">Passer à la caisse</button>
    <a href="#" class="clear-link" onclick="clearCart(); return false;">Vider le panier</a>
  </div>
</div>

<script src="script.js"></script>
<script>
function toggleUserMenu(e) {
  e.preventDefault();
  const btn = document.getElementById('userMenuBtn');
  const dropdown = document.getElementById('userDropdown');
  const isOpen = dropdown.classList.contains('open');
  
  if (isOpen) {
    dropdown.classList.remove('open');
    btn.classList.remove('active');
  } else {
    dropdown.classList.add('open');
    btn.classList.add('active');
  }
}

document.addEventListener('click', function(e) {
  const userMenu = document.querySelector('.user-menu');
  if (userMenu && !userMenu.contains(e.target)) {
    const dropdown = document.getElementById('userDropdown');
    const btn = document.getElementById('userMenuBtn');
    if (dropdown) dropdown.classList.remove('open');
    if (btn) btn.classList.remove('active');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  loadCart();
  updateCartUI();
});
</script>

</body>
</html>