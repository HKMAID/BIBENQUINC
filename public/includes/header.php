<?php
/**
 * Shared header partial.
 * Expects: $authUser, $authName, $flash (already set by calling page)
 * Param: $activeNav — optional string: 'catalogue', 'contact', 'profile', 'achats', 'pref'
 */
$activeNav = $activeNav ?? '';
$flash = $flash ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'BibenQuinc') ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDesc ?? 'BibenQuinc - Votre quincaillerie en ligne') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="catalogue.css">
  <?php foreach ($extraCss ?? [] as $css): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
  <?php endforeach; ?>
</head>
<body>

<?php if ($flash ?? null): ?>
  <div class="flash-msg flash-<?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
  </div>
<?php endif; ?>

<div class="app scroll-page">
  <header class="site-header">
    <a href="index.php" class="logo">
      <span class="logo-bar"></span>
      <span class="logo-bolt"> </span>
      <span class="logo-image">
        <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word logo-quinc">
        <img src="img/logo2.png" alt="BibenQuinc Logo Électricité" class="logo-word logo-elec">
      </span>
    </a>

    <nav class="nav">
      <a href="catalogue.php" class="<?= $activeNav === 'catalogue' ? 'nav-active' : '' ?>">Catalogue</a>
      <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
      <a href="contact.php"   class="<?= $activeNav === 'contact'   ? 'nav-active' : '' ?>">Contact</a>
      <?php endif; ?>

      <?php if ($authUser ?? null): ?>
        <div class="user-menu">
          <button class="user-btn" id="userMenuBtn" onclick="toggleUserMenu(event)">
            <?= htmlspecialchars((string) $authName, ENT_QUOTES) ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div class="user-dropdown" id="userDropdown">
            <a href="profile.php" class="<?= $activeNav === 'profile' ? 'active' : '' ?>">Mon profil</a>
            <?php if (($authUser['type'] ?? null) === 'professionnel'): ?>
            <a href="dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Tableau de bord</a>
            <?php else: ?>
            <a href="achats.php"  class="<?= $activeNav === 'achats'  ? 'active' : '' ?>">Mes achats</a>
            <a href="pref.php"    class="<?= $activeNav === 'pref'    ? 'active' : '' ?>">Mes préférences</a>
            <?php endif; ?>
            <a href="logout.php" style="color:#ef4444">Déconnexion</a>
          </div>
        </div>
      <?php else: ?>
        <a href="cnx.php">Connexion</a>
      <?php endif; ?>

      <?php if (!($authUser['type'] ?? null) || $authUser['type'] !== 'professionnel'): ?>
      <a href="#" class="cart-btn" id="cartBtn" onclick="openDrawer(); return false;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="count-chip" id="chipCount">0</span>
      </a>
      <?php endif; ?>
    </nav>
  </header>
