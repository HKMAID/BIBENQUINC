<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cnx.css">
</head>
<body>

<div style="padding:20px;width:100%;display:flex;flex-direction:column;align-items:center;">
    <a href="index.php" class="back-link">← Retour à l'acceuil</a>

    <div class="auth-page-card">
        <div class="auth-page-header">
            <div class="logo">
                <span class="logo-bar"></span>
                <span class="logo-image">
                    <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word logo-quinc">
                </span>
            </div>
        </div>

        <form class="auth-form" method="post" action="">
            <h2>Se connecter</h2>
            <div class="form-group">
                <label for="login-email">Email</label>
                <input type="email" id="login-email" name="email" required placeholder="votre@email.com">
            </div>
            <div class="form-group">
                <label for="login-password">Mot de passe</label>
                <input type="password" id="login-password" name="password" required placeholder="••••••••••">
            </div>
            <button type="submit" class="auth-submit-btn">Connexion →</button>
        </form>

        <div class="auth-footer">
            <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
        </div>
    </div>
</div>
<footer class="site-footer">
      <p><a href="contact.php">Contact</a></p>
      <p>&copy; 2026 BibenQuinc. Tous droits réservés.</p>
</footer>
</body>
</html>
