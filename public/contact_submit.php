<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

initSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$autre_sujet = trim($_POST['autre_sujet'] ?? '');

if (empty($subject) || empty($message)) {
    echo json_encode(['error' => 'Tous les champs sont requis']);
    exit;
}

if ($subject === 'autre' && empty($autre_sujet)) {
    echo json_encode(['error' => 'Veuillez préciser votre sujet']);
    exit;
}

$final_subject = $subject === 'autre' ? $autre_sujet : $subject;

$contact_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'user' => $_SESSION['nom'] ?? 'Anonyme',
    'email' => $_SESSION['email'] ?? '',
    'subject' => $final_subject,
    'message' => $message,
];

$contacts_file = __DIR__ . '/../storage/contacts.json';
$contacts = file_exists($contacts_file) ? json_decode(file_get_contents($contacts_file), true) : [];
$contacts[] = $contact_data;
file_put_contents($contacts_file, json_encode($contacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => 'Message envoyé avec succès']);
?>