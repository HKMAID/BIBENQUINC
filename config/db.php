<?php
declare(strict_types=1);

/* Connexion BDD */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host   = 'sql308.infinityfree.com';
    $port   = '3306';
    $dbname = '';
    $user   = '';
    $pass   = '';        
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('DB connection error: ' . $e->getMessage());
        http_response_code(500);
        die('Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
    }

    return $pdo;
}
