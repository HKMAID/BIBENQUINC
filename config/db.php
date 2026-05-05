<?php
declare(strict_types=1);

/**
 * Retourne la connexion PDO partagée.
 * Paramètres XAMPP par défaut — adapter si nécessaire.
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host   = '127.0.0.1';
    $port   = '3306';
    $dbname = 'bibquinc';
    $user   = 'root';
    $pass   = '';          // XAMPP : pas de mot de passe par défaut
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // En production, ne pas exposer le message d'erreur
        error_log('DB connection error: ' . $e->getMessage());
        http_response_code(500);
        die('Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
    }

    return $pdo;
}
