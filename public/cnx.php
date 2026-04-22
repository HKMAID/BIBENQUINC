<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth_store.php';

initSession();

if (isLoggedIn()) {
    redirectTo('index.php');
}

$error = '';
$oldEmail = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($oldEmail === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $result = attemptLogin($oldEmail, $password);
        if ($result['ok']) {
            loginUser($result['user']);
            setFlash('success', 'Connexion reussie.');
            redirectTo('index.php');
        }
        $error = $result['error'];
    }
}
?>
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
    <a href="index.php" class="back-link">← Retour au catalogue</a>

    <div class="auth-page-card">
        <?php if ($flash && $flash['type'] === 'success'): ?>
            <p style="margin:14px 20px 0;color:#16a34a;font-family:var(--fb);"><?= htmlspecialchars($flash['message'], ENT_QUOTES) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p style="margin:14px 20px 0;color:#ef4444;font-family:var(--fb);"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
        <?php endif; ?>
        <div class="auth-page-header">
            <div class="logo">
                <span class="logo-bar"></span>
                <span class="logo-image">
                    <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word logo-quinc">
                </span>
            </div>
        </div>

        <form class="auth-form" method="post" action="" id="login-form" novalidate>
            <h2>Se connecter</h2>
            <div class="form-group">
                <label for="login-email">Email</label>
                <input type="email" id="login-email" name="email" required placeholder="votre@email.com" value="<?= htmlspecialchars($oldEmail, ENT_QUOTES) ?>">
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

<script defer src="js/auth-form.js"></script>
</body>
</html>
