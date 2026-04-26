<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth_store.php';

initSession();

if (isLoggedIn()) {
    redirectTo('index.php');
}

$error = '';
$old = [
    'lastname' => '',
    'firstname' => '',
    'email' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($password !== $password2) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $result = registerUser([
            'lastname' => $old['lastname'],
            'firstname' => $old['firstname'],
            'email' => $old['email'],
            'phone' => $old['phone'],
            'password' => $password,
        ]);

        if ($result['ok']) {
            loginUser($result['user']);
            setFlash('success', 'Compte cree avec succes.');
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
    <title>Inscription - BibenQuinc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="register.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div style="padding:20px;width:100%;display:flex;flex-direction:column;align-items:center;margin-top: 30px">
    <a href="index.php" class="back-link">← Retour au catalogue</a>

    <div class="auth-page-card">
        <div class="auth-page-header">
            <div class="logo">
                <span class="logo-bar"></span>
                <span class="logo-image">
                    <img src="img/logo1.png" alt="BibenQuinc Logo" class="logo-word logo-quinc">
                </span>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <p style="margin:14px 20px 0;color:#ef4444;font-family:var(--fb);"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
        <?php endif; ?>
        <form class="auth-form" method="post" action="" id="register-form" novalidate>
            <h2>Créer un compte</h2>
            <div class="form-group">
                <label for="signup-lastname">Nom</label>
                <input type="text" id="signup-lastname" name="lastname" required placeholder="Votre nom de famille" value="<?= htmlspecialchars($old['lastname'], ENT_QUOTES) ?>">
            </div>
            <div class="form-group">
                <label for="signup-firstname">Prénom</label>
                <input type="text" id="signup-firstname" name="firstname" required placeholder="Votre prénom" value="<?= htmlspecialchars($old['firstname'], ENT_QUOTES) ?>">
            </div>
            <div class="form-group">
                <label for="signup-email">Email</label>
                <input type="email" id="signup-email" name="email" required placeholder="votre@email.com" value="<?= htmlspecialchars($old['email'], ENT_QUOTES) ?>">
            </div>
            <div class="form-group">
                <label for="signup-number">Numéro de téléphone</label>
                <input type="tel" id="signup-number" name="phone" required placeholder="Votre numéro de téléphone" value="<?= htmlspecialchars($old['phone'], ENT_QUOTES) ?>">
            </div>
            <div class="form-group">
                <label for="signup-password">Mot de passe</label>
                <input type="password" id="signup-password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label for="signup-password2">Confirmer le mot de passe</label>
                <input type="password" id="signup-password2" name="password2" required placeholder="••••••••">
            </div>
            <button type="submit" class="auth-submit-btn">S'inscrire →</button>
        </form>

        <div class="auth-footer">
            <p>Déjà un compte ? <a href="cnx.php">Se connecter</a></p>
        </div>
    </div>
</div>

<script defer src="js/auth-form.js"></script>

<footer class="site-footer">
      <p><a href="contact.php">Contact</a></p>
      <p>&copy; 2026 BibenQuinc. Tous droits réservés.</p>
</footer>

</body>
</html>
