<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/db.php';

function _normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function _rowToUser(array $row): array
{
    return [
        'id'        => (int) $row['idUtili'],
        'firstname' => $row['prenom'],
        'lastname'  => $row['nom'],
        'email'     => $row['email'],
        'phone'     => $row['numTel'],
        'type'      => $row['type'],
        'created_at'=> $row['created_at'],
    ];
}

function findUserByEmail(string $email): ?array
{
    $email = _normalizeEmail($email);
    $stmt  = getPDO()->prepare(
        'SELECT * FROM utilisateur WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    return $row ? $row : null;  
}

function findUserById(int $id): ?array
{
    $stmt = getPDO()->prepare(
        'SELECT * FROM utilisateur WHERE idUtili = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ? _rowToUser($row) : null;
}

function registerUser(array $payload): array
{
    $firstname = trim((string) ($payload['firstname'] ?? ''));
    $lastname  = trim((string) ($payload['lastname']  ?? ''));
    $email     = _normalizeEmail((string) ($payload['email']  ?? ''));
    $phone     = trim((string) ($payload['phone']  ?? ''));
    $password  = (string) ($payload['password'] ?? '');
    $type      = in_array($payload['type'] ?? '', ['client','professionnel'], true)
                 ? $payload['type']
                 : 'client';

    if ($firstname === '' || $lastname === '' || $email === '' || $phone === '') {
        return ['ok' => false, 'error' => 'Tous les champs sont obligatoires.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Adresse email invalide.'];
    }
    if (!preg_match('/^[0-9]{9,10}$/', $phone)) {
        return ['ok' => false, 'error' => 'Numéro de téléphone invalide (9 ou 10 chiffres).'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères.'];
    }

    if (findUserByEmail($email) !== null) {
        return ['ok' => false, 'error' => 'Un compte existe déjà avec cet email.'];
    }
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = getPDO()->prepare(
        'INSERT INTO utilisateur (nom, prenom, email, numTel, mdp, type, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$lastname, $firstname, $email, $phone, $hash, $type]);

    $newId = (int) getPDO()->lastInsertId();

    $user = [
        'id'        => $newId,
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'email'     => $email,
        'phone'     => $phone,
        'type'      => $type,
    ];

    return ['ok' => true, 'user' => $user];
}

function attemptLogin(string $email, string $password): array
{
    $row = findUserByEmail($email);

    if ($row === null) {
        password_verify($password, '$2y$12$invalidhashtopreventtimingattack');
        return ['ok' => false, 'error' => 'Email ou mot de passe incorrect.'];
    }

    if (!password_verify($password, (string) $row['mdp'])) {
        return ['ok' => false, 'error' => 'Email ou mot de passe incorrect.'];
    }

    getPDO()
        ->prepare('UPDATE utilisateur SET last_login = NOW() WHERE idUtili = ?')
        ->execute([$row['idUtili']]);

    return ['ok' => true, 'user' => _rowToUser($row)];
}

function updateUser(int $id, array $data, string $newPassword = ''): array
{
    $firstname = trim((string) ($data['firstname'] ?? ''));
    $lastname  = trim((string) ($data['lastname']  ?? ''));
    $phone     = trim((string) ($data['phone']     ?? ''));

    if ($firstname === '' || $lastname === '') {
        return ['ok' => false, 'error' => 'Prénom et nom sont obligatoires.'];
    }

    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            return ['ok' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères.'];
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        getPDO()->prepare(
            'UPDATE utilisateur SET prenom=?, nom=?, numTel=?, mdp=? WHERE idUtili=?'
        )->execute([$firstname, $lastname, $phone, $hash, $id]);
    } else {
        getPDO()->prepare(
            'UPDATE utilisateur SET prenom=?, nom=?, numTel=? WHERE idUtili=?'
        )->execute([$firstname, $lastname, $phone, $id]);
    }

    return ['ok' => true];
}
