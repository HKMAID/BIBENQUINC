<?php
/**
 * wishlist_ajax.php — AJAX endpoint for wishlist add/remove
 * Accepts POST JSON: { "action": "add"|"remove", "name": "product name" }
 * Returns JSON: { "ok": true, "isFav": bool, "count": int } or { "ok": false, "error": "..." }
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/products.php';

header('Content-Type: application/json; charset=utf-8');

initSession();
$authUser = currentUser();

if (!$authUser) {
    echo json_encode(['ok' => false, 'error' => 'Non connecté', 'redirect' => 'cnx.php']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = trim($input['action'] ?? '');
$name   = trim($input['name'] ?? '');

if (!in_array($action, ['add', 'remove'], true) || $name === '') {
    echo json_encode(['ok' => false, 'error' => 'Paramètres invalides']);
    exit;
}

try {
    require_once __DIR__ . '/../config/db.php';
    $pdo = getPDO();

    $idArt = getProductIdByName($name);
    if (!$idArt) {
        echo json_encode(['ok' => false, 'error' => 'Article introuvable']);
        exit;
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT IGNORE INTO favori (idClient, idArticle) VALUES (?, ?)');
        $stmt->execute([$authUser['id'], $idArt]);
        $isFav = true;
    } else {
        $stmt = $pdo->prepare('DELETE FROM favori WHERE idClient = ? AND idArticle = ?');
        $stmt->execute([$authUser['id'], $idArt]);
        $isFav = false;
    }

    // Get updated count
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM favori WHERE idClient = ?');
    $countStmt->execute([$authUser['id']]);
    $count = (int) $countStmt->fetchColumn();

    echo json_encode(['ok' => true, 'isFav' => $isFav, 'count' => $count]);
} catch (Exception $e) {
    error_log('Wishlist AJAX error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Erreur base de données']);
}
