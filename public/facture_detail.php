<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/db.php';

initSession();
$authUser = currentUser();

// Only authenticated professionals can consult invoices
if (!$authUser || ($authUser['type'] ?? 'client') !== 'professionnel') {
    echo json_encode([]);
    exit;
}

$idAchat = (int) ($_GET['idAchat'] ?? 0);
if ($idAchat <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('
        SELECT aa.quantite, a.intitule, a.prix, a.photo
        FROM article_achat aa
        JOIN article a ON aa.idArticle = a.idArt
        WHERE aa.idAchat = ?
        ORDER BY a.intitule
    ');
    $stmt->execute([$idAchat]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (Exception $e) {
    error_log('facture_detail error: ' . $e->getMessage());
    echo json_encode([]);
}
