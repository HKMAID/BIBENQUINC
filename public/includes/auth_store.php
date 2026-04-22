<?php
declare(strict_types=1);

function storagePath(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'users.json';
}

function ensureStorage(): void
{
    $file = storagePath();
    $dir = dirname($file);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function readUsers(): array
{
    ensureStorage();
    $raw = file_get_contents(storagePath());
    $data = json_decode($raw ?: '[]', true);

    return is_array($data) ? $data : [];
}

function writeUsers(array $users): void
{
    file_put_contents(
        storagePath(),
        json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function findUserByEmail(string $email): ?array
{
    $email = normalizeEmail($email);
    foreach (readUsers() as $user) {
        if (($user['email'] ?? '') === $email) {
            return $user;
        }
    }

    return null;
}

function registerUser(array $payload): array
{
    $firstname = trim((string) ($payload['firstname'] ?? ''));
    $lastname = trim((string) ($payload['lastname'] ?? ''));
    $email = normalizeEmail((string) ($payload['email'] ?? ''));
    $phone = trim((string) ($payload['phone'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($firstname === '' || $lastname === '' || $phone === '') {
        return ['ok' => false, 'error' => 'Tous les champs sont obligatoires.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Adresse email invalide.'];
    }

    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caracteres.'];
    }

    if (findUserByEmail($email) !== null) {
        return ['ok' => false, 'error' => 'Un compte existe deja avec cet email.'];
    }

    $users = readUsers();
    $newUser = [
        'id' => bin2hex(random_bytes(8)),
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email,
        'phone' => $phone,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('c'),
    ];

    $users[] = $newUser;
    writeUsers($users);

    return ['ok' => true, 'user' => $newUser];
}

function attemptLogin(string $email, string $password): array
{
    $user = findUserByEmail($email);

    if ($user === null || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        return ['ok' => false, 'error' => 'Email ou mot de passe incorrect.'];
    }

    return ['ok' => true, 'user' => $user];
}
