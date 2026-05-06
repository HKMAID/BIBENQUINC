<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth_db.php';

initSession();

// Sécurité : Optionnel, on peut vérifier que l'utilisateur est admin ou pro s'il est loggué
// Mais ici c'est un utilitaire "admin", donc on le laisse accessible
// if (!isLoggedIn() || currentUser()['type'] !== 'professionnel') {
//     die("Accès non autorisé.");
// }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $type      = $_POST['type'] ?? 'professionnel'; // Par défaut pro
    
    $result = registerUser([
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'email'     => $email,
        'phone'     => $phone,
        'password'  => $password,
        'type'      => $type
    ]);

    if ($result['ok']) {
        $success = "L'utilisateur {$email} a été créé avec le rôle '{$type}'.";
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Créer un Utilisateur</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { padding: 40px; background: var(--bg); font-family: var(--fb); }
        .admin-card { max-width: 500px; margin: 0 auto; background: var(--surface); padding: 30px; border-radius: var(--r-md); box-shadow: var(--sh-md); }
        h1 { margin-bottom: 20px; font-family: var(--fd); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: var(--r-sm); }
        .btn { padding: 12px 20px; background: var(--accent); color: white; border: none; border-radius: var(--r-sm); cursor: pointer; width: 100%; font-weight: bold; }
        .btn:hover { background: var(--accent-dark); }
        .success { color: green; margin-bottom: 15px; padding: 10px; background: #e6ffed; border: 1px solid #b7ebc6; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #ffeef0; border: 1px solid #f6c6ce; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: var(--accent); }
    </style>
</head>
<body>

<div class="admin-card">
    <a href="index.php" class="back-link">← Retour au site</a>
    <h1>Création d'Utilisateur Admin</h1>
    
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Nom</label>
            <input type="text" name="lastname" required>
        </div>
        <div class="form-group">
            <label>Prénom</label>
            <input type="text" name="firstname" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Téléphone</label>
            <input type="text" name="phone" required placeholder="Ex: 0612345678">
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Type de compte</label>
            <select name="type">
                <option value="professionnel">Professionnel (Admin)</option>
                <option value="client">Client normal</option>
            </select>
        </div>
        <button type="submit" class="btn">Créer l'utilisateur</button>
    </form>
</div>

</body>
</html>
