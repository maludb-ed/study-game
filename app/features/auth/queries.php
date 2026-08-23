<?php
declare(strict_types=1);

function count_users(PDO $pdo): int
{
    return (int) $pdo->query('SELECT count(*) FROM users')->fetchColumn();
}

function find_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    return $user === false ? null : $user;
}

function find_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    return $user === false ? null : $user;
}

/** First registered user becomes admin (bootstrap rule for the study group). */
function insert_user(PDO $pdo, string $email, string $displayName, ?string $passwordHash, bool $verified): array
{
    $role = count_users($pdo) === 0 ? 'admin' : 'member';
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO users (email, display_name, password_hash, role, email_verified_at)
        VALUES (:email, :display_name, :password_hash, :role, :verified_at)
        RETURNING id, email, display_name, role
    SQL);
    $stmt->execute([
        'email' => $email, 'display_name' => $displayName,
        'password_hash' => $passwordHash, 'role' => $role,
        'verified_at' => $verified ? date('c') : null,
    ]);
    return $stmt->fetch();
}

function set_user_password(PDO $pdo, int $userId, string $hash): void
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $stmt->execute(['hash' => $hash, 'id' => $userId]);
}

function mark_email_verified(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE users SET email_verified_at = now() WHERE id = :id AND email_verified_at IS NULL');
    $stmt->execute(['id' => $userId]);
}

// --- External identities (google-signin.md) -------------------------------

function find_identity(PDO $pdo, string $provider, string $providerUserId): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT * FROM auth_identities
        WHERE provider = :provider AND provider_user_id = :sub LIMIT 1
    SQL);
    $stmt->execute(['provider' => $provider, 'sub' => $providerUserId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function user_has_identity(PDO $pdo, int $userId, string $provider): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM auth_identities WHERE user_id = :id AND provider = :provider LIMIT 1');
    $stmt->execute(['id' => $userId, 'provider' => $provider]);
    return $stmt->fetch() !== false;
}

function insert_identity(PDO $pdo, int $userId, string $provider, string $providerUserId, string $email): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO auth_identities (user_id, provider, provider_user_id, email_at_provider)
        VALUES (:user_id, :provider, :sub, :email)
        RETURNING id
    SQL);
    $stmt->execute(['user_id' => $userId, 'provider' => $provider, 'sub' => $providerUserId, 'email' => $email]);
    return $stmt->fetch();
}

// --- One-time tokens (reset + verify) -------------------------------------

function insert_user_token(PDO $pdo, int $userId, string $purpose, string $tokenHash, string $expiresInterval): void
{
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO user_tokens (user_id, purpose, token_hash, expires_at)
        VALUES (:user_id, :purpose, :hash, now() + :expires::interval)
    SQL);
    $stmt->execute(['user_id' => $userId, 'purpose' => $purpose, 'hash' => $tokenHash, 'expires' => $expiresInterval]);
}

function find_valid_user_token(PDO $pdo, string $purpose, string $tokenHash): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT * FROM user_tokens
        WHERE purpose = :purpose AND token_hash = :hash
          AND used_at IS NULL AND expires_at > now()
        LIMIT 1
    SQL);
    $stmt->execute(['purpose' => $purpose, 'hash' => $tokenHash]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function consume_user_token(PDO $pdo, int $tokenId): void
{
    $stmt = $pdo->prepare('UPDATE user_tokens SET used_at = now() WHERE id = :id');
    $stmt->execute(['id' => $tokenId]);
}

// --- TOTP 2FA (totp-2fa.md) ------------------------------------------------

function enable_user_totp(PDO $pdo, int $userId, string $encryptedSecret): void
{
    $stmt = $pdo->prepare(<<<'SQL'
        UPDATE users SET totp_secret = :secret, totp_enabled_at = now(), totp_last_timestep = 0
        WHERE id = :id
    SQL);
    $stmt->execute(['secret' => $encryptedSecret, 'id' => $userId]);
}

function disable_user_totp(PDO $pdo, int $userId): void
{
    $pdo->prepare('UPDATE users SET totp_secret = NULL, totp_enabled_at = NULL, totp_last_timestep = NULL WHERE id = :id')
        ->execute(['id' => $userId]);
    $pdo->prepare('DELETE FROM totp_recovery_codes WHERE user_id = :id')
        ->execute(['id' => $userId]);
}

function store_totp_timestep(PDO $pdo, int $userId, int $timestep): void
{
    $stmt = $pdo->prepare('UPDATE users SET totp_last_timestep = :step WHERE id = :id');
    $stmt->execute(['step' => $timestep, 'id' => $userId]);
}

function insert_recovery_codes(PDO $pdo, int $userId, array $hashes): void
{
    $stmt = $pdo->prepare('INSERT INTO totp_recovery_codes (user_id, code_hash) VALUES (:id, :hash)');
    foreach ($hashes as $hash) {
        $stmt->execute(['id' => $userId, 'hash' => $hash]);
    }
}

function find_unused_recovery_codes(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT id, code_hash FROM totp_recovery_codes WHERE user_id = :id AND used_at IS NULL');
    $stmt->execute(['id' => $userId]);
    return $stmt->fetchAll();
}

function mark_recovery_code_used(PDO $pdo, int $codeId): void
{
    $stmt = $pdo->prepare('UPDATE totp_recovery_codes SET used_at = now() WHERE id = :id');
    $stmt->execute(['id' => $codeId]);
}
