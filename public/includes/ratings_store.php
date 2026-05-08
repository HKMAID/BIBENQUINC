<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

function saveProductRating(string $userId, string $productId, float $rating): void
{
    $pdo = getPDO();
    
    require_once __DIR__ . '/products.php';
    $product = findProductBySlug($productId);
    if (!$product) return;
    
    $stmt = $pdo->prepare('SELECT idArt FROM article WHERE intitule = ? LIMIT 1');
    $stmt->execute([$product['name']]);
    $idArt = $stmt->fetchColumn();
    
    if ($idArt) {
        $stmt = $pdo->prepare('
            INSERT INTO note (idClient, idArticle, note) 
            VALUES (:idClient, :idArticle, :note)
            ON DUPLICATE KEY UPDATE note = VALUES(note)
        ');
        $stmt->execute([
            ':idClient' => (int)$userId,
            ':idArticle' => $idArt,
            ':note' => $rating,
        ]);
    }
}

function getUserRating(string $userId, string $productId): ?float
{
    $pdo = getPDO();
    
    require_once __DIR__ . '/products.php';
    $product = findProductBySlug($productId);
    if (!$product) return null;
    
    $stmt = $pdo->prepare('
        SELECT n.note 
        FROM note n 
        JOIN article a ON n.idArticle = a.idArt 
        WHERE a.intitule = ? AND n.idClient = ?
        LIMIT 1
    ');
    $stmt->execute([$product['name'], $userId]);
    
    $res = $stmt->fetchColumn();
    return $res !== false ? (float)$res : null;
}

function getProductGlobalRating(string $productId): array
{
    $pdo = getPDO();
    
    require_once __DIR__ . '/products.php';
    $product = findProductBySlug($productId);
    if (!$product) return ['average' => null, 'count' => 0];
    
    $stmt = $pdo->prepare('
        SELECT AVG(n.note) AS avg_rating, COUNT(*) AS total_votes
        FROM note n 
        JOIN article a ON n.idArticle = a.idArt 
        WHERE a.intitule = ?
    ');
    $stmt->execute([$product['name']]);
    $row = $stmt->fetch();

    $avg = isset($row['avg_rating']) && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : null;
    $count = isset($row['total_votes']) ? (int) $row['total_votes'] : 0;

    return [
        'average' => $avg,
        'count' => $count,
    ];
}

