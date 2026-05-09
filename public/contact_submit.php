<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/db.php';

initSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$authUser = currentUser();
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Vous devez être connecté pour envoyer un message.']);
    exit;
}

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$autre_sujet = trim($_POST['autre_sujet'] ?? '');

if (empty($subject) || empty($message)) {
    echo json_encode(['error' => 'Tous les champs sont requis.']);
    exit;
}

if ($subject === 'autre' && empty($autre_sujet)) {
    echo json_encode(['error' => 'Veuillez préciser votre sujet.']);
    exit;
}

$final_subject = $subject === 'autre' ? $autre_sujet : $subject;

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO message (idClient, theme, contenu, date_msg) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$authUser['id'], $final_subject, $message]);
    echo json_encode(['success' => 'Message envoyé avec succès.']);
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    echo json_encode(['error' => 'Erreur lors de l\'envoi de votre message.']);
}
?>