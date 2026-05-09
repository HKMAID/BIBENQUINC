<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth_db.php';

initSession();

if (isLoggedIn()) {
    redirectTo('catalogue.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('cnx.php');
}

$email    = trim((string) ($_POST['email']    ?? ''));
$password = (string)       ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    setFlash('error', 'Veuillez remplir tous les champs.');
    redirectTo('cnx.php');
}

$result = attemptLogin($email, $password);

if (!$result['ok']) {
    setFlash('error', $result['error']);
    redirectTo('cnx.php');
}

loginUser($result['user']);
setFlash('success', 'Connexion réussie. Bienvenue !');

$dest = ($result['user']['type'] ?? 'client') === 'professionnel'
        ? 'dashboard.php'
        : 'catalogue.php';

redirectTo($dest);
