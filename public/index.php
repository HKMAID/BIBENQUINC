<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

initSession();
$authUser = currentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="acceuil.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="landing-body">
  <div class="landing-app">
    <?php if ($authUser): ?>
      <a href="logout.php" class="landing-login-btn">Deconnexion</a>
    <?php else: ?>
      <a href="cnx.php" class="landing-login-btn">Connexion</a>
    <?php endif; ?>
    <div class="landing-split">
      
      <div class="landing-left">
        <div class="landing-logo-container">
          <div class="logo landing-logo">
            <span class="logo-image">
              <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word logo-quinc">
            </span>
          </div>
          <p class="landing-subtitle">Votre partenaire de confiance pour la quincaillerie et l'électricité</p>
        </div>
      </div>
      
      <div class="landing-right">
        <div class="landing-content">
          <h1 class="landing-title">Bienvenue chez BibenQuinc</h1>
          <p class="landing-desc">Découvrez notre large gamme de produits de qualité professionnelle pour tous vos travaux de construction, d'aménagement et de rénovation.</p>
          <a href="catalogue.php" class="landing-btn">
            Consultez notre catalogue 
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
      </div>
      
    </div>
  </div>
</body>
</html>
