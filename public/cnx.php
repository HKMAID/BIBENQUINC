<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth_db.php';

initSession();

if (isLoggedIn()) {
    redirectTo('index.php');
}

$error    = '';
$oldEmail = '';
$flash    = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = trim((string) ($_POST['email']    ?? ''));
    $password = (string)       ($_POST['password'] ?? '');

    if ($oldEmail === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $result = attemptLogin($oldEmail, $password);

        if ($result['ok']) {
            loginUser($result['user']);
            setFlash('success', 'Connexion réussie. Bienvenue !');

            $dest = ($result['user']['type'] ?? 'client') === 'professionnel'
                    ? 'dashboard.php'
                    : 'catalogue.php';
            redirectTo($dest);
        }

        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion — BibenQuinc</title>
    <meta name="description" content="Connectez-vous à votre compte BibenQuinc.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="cnx.css">
</head>
<body>

<div class="auth-page-wrap">
    <a href="catalogue.php" class="back-link">← Retour au catalogue</a>

    <div class="auth-page-card">

        <div class="auth-page-header">
            <div class="logo">
                <span class="logo-bar"></span>
                <span class="logo-image">
                    <img src="img/logo1.png" alt="BibenQuinc" class="logo-word logo-quinc">
                </span>
            </div>
        </div>

        <?php if ($flash && $flash['type'] === 'success'): ?>
            <p class="auth-inline-success"><?= htmlspecialchars($flash['message'], ENT_QUOTES) ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="auth-inline-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
        <?php endif; ?>

        <form class="auth-form" method="post" action="" id="login-form" novalidate>
            <h2>Se connecter</h2>

            <div class="form-group">
                <label for="login-email">Email</label>
                <input type="email" id="login-email" name="email" required
                       placeholder="votre@email.com" autocomplete="email"
                       value="<?= htmlspecialchars($oldEmail, ENT_QUOTES) ?>">
            </div>

            <div class="form-group pwd-group">
                <label for="login-password">Mot de passe</label>
                <div class="pwd-wrap">
                    <input type="password" id="login-password" name="password" required
                           placeholder="••••••••••" autocomplete="current-password">
                    <button type="button" class="pwd-toggle"
                            onclick="togglePwd('login-password',this)" title="Afficher/Masquer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-submit-btn" id="login-btn">Connexion →</button>
        </form>

        <div class="auth-footer">
            <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
        </div>
    </div>
</div>

<footer class="site-footer">
    <p><a href="contact.php">Contact</a></p>
    <p>© 2026 BibenQuinc. Tous droits réservés.</p>
</footer>

<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.style.opacity = show ? '1' : '0.5';
}
</script>
</body>
</html>
