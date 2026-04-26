<?php
declare(strict_types=1);

function ratingsDbPath(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'ratings.sqlite';
}

function ratingsPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbPath = ratingsDbPath();
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS product_ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            product_id TEXT NOT NULL,
            rating REAL NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE(user_id, product_id)
        )'
    );

    return $pdo;
}

function saveProductRating(string $userId, string $productId, float $rating): void
{
    $pdo = ratingsPdo();
    $now = date('c');

    $stmt = $pdo->prepare(
        'INSERT INTO product_ratings (user_id, product_id, rating, created_at, updated_at)
         VALUES (:user_id, :product_id, :rating, :created_at, :updated_at)
         ON CONFLICT(user_id, product_id)
         DO UPDATE SET rating = excluded.rating, updated_at = excluded.updated_at'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':product_id' => $productId,
        ':rating' => $rating,
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);
}

function getUserRating(string $userId, string $productId): ?float
{
    $stmt = ratingsPdo()->prepare(
        'SELECT rating FROM product_ratings WHERE user_id = :user_id AND product_id = :product_id LIMIT 1'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':product_id' => $productId,
    ]);

    $row = $stmt->fetch();
    return $row ? (float) $row['rating'] : null;
}

function getProductGlobalRating(string $productId): array
{
    $stmt = ratingsPdo()->prepare(
        'SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_votes
         FROM product_ratings
         WHERE product_id = :product_id'
    );
    $stmt->execute([':product_id' => $productId]);
    $row = $stmt->fetch();

    $avg = isset($row['avg_rating']) && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : null;
    $count = isset($row['total_votes']) ? (int) $row['total_votes'] : 0;

    return [
        'average' => $avg,
        'count' => $count,
    ];
}
