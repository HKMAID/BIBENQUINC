<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth_db.php';

initSession();

if (isLoggedIn()) {
    redirectTo('index.php');
}

$error = '';
$old   = ['lastname' => '', 'firstname' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $password  = (string) ($_POST['password']  ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($password !== $password2) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $result = registerUser([
            'lastname'  => $old['lastname'],
            'firstname' => $old['firstname'],
            'email'     => $old['email'],
            'phone'     => $old['phone'],
            'password'  => $password,
            'type'      => 'client',
        ]);

        if ($result['ok']) {
            loginUser($result['user']);
            setFlash('success', 'Compte créé avec succès. Bienvenue !');
            redirectTo('catalogue.php');
        }

        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription — BibenQuinc</title>
    <meta name="description" content="Créez votre compte BibenQuinc pour accéder au catalogue.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="register.css">
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

        <?php if ($error !== ''): ?>
            <p class="auth-inline-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
        <?php endif; ?>

        <form class="auth-form" method="post" action="" id="register-form" novalidate>
            <h2>Créer un compte</h2>

            <div class="form-group">
                <label for="signup-lastname">Nom <span class="req">*</span></label>
                <input type="text" id="signup-lastname" name="lastname" required
                       placeholder="Votre nom de famille"
                       value="<?= htmlspecialchars($old['lastname'], ENT_QUOTES) ?>">
            </div>

            <div class="form-group">
                <label for="signup-firstname">Prénom <span class="req">*</span></label>
                <input type="text" id="signup-firstname" name="firstname" required
                       placeholder="Votre prénom"
                       value="<?= htmlspecialchars($old['firstname'], ENT_QUOTES) ?>">
            </div>

            <div class="form-group">
                <label for="signup-email">Email <span class="req">*</span></label>
                <input type="email" id="signup-email" name="email" required
                       placeholder="votre@email.com"
                       value="<?= htmlspecialchars($old['email'], ENT_QUOTES) ?>">
            </div>

            <div class="form-group">
                <label for="signup-phone">Téléphone <span class="req">*</span></label>
                <input type="tel" id="signup-phone" name="phone" required
                       placeholder="0XXXXXXXXX"
                       value="<?= htmlspecialchars($old['phone'], ENT_QUOTES) ?>">
            </div>

            <div class="form-group pwd-group">
                <label for="signup-password">Mot de passe <span class="req">*</span></label>
                <div class="pwd-wrap">
                    <input type="password" id="signup-password" name="password" required
                           placeholder="Au moins 6 caractères" autocomplete="new-password">
                    <button type="button" class="pwd-toggle"
                            onclick="togglePwd('signup-password',this)" title="Afficher/Masquer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group pwd-group">
                <label for="signup-password2">Confirmer le mot de passe <span class="req">*</span></label>
                <div class="pwd-wrap">
                    <input type="password" id="signup-password2" name="password2" required
                           placeholder="Répétez le mot de passe" autocomplete="new-password">
                    <button type="button" class="pwd-toggle"
                            onclick="togglePwd('signup-password2',this)" title="Afficher/Masquer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-submit-btn" id="register-btn">S'inscrire →</button>
        </form>

        <div class="auth-footer">
            <p>Déjà un compte ? <a href="cnx.php">Se connecter</a></p>
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
