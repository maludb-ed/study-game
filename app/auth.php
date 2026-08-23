<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Guard ALL unsafe methods; accepts the hidden field OR the X-CSRF-Token header (HTMX). */
function verify_csrf(): void
{
    $unsafe = ['POST', 'PUT', 'PATCH', 'DELETE'];
    if (!in_array($_SERVER['REQUEST_METHOD'], $unsafe, true)) {
        return;
    }
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $sent)) {
        http_response_code(403);
        exit('CSRF validation failed.');
    }
}

function current_user_id(): ?int
{
    $id = $_SESSION['user_id'] ?? null;
    return is_int($id) ? $id : null;
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = current_user_id();
    if ($id === null) {
        return $user = null;
    }
    $stmt = db()->prepare('SELECT id, email, display_name, role, totp_enabled_at FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $user = ($row === false ? null : $row);
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('/login');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}

/** Establish the session after ANY successful primary+secondary auth. */
function establish_session(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_started_at']);
    unset($_SESSION['csrf_token']);   // mint fresh post-login
    csrf_token();
}

// --- Brute-force throttle (php-session-auth non-negotiable) ---------------

function too_many_attempts(PDO $pdo, ?string $email, string $ip, string $kind = 'password'): bool
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT
          count(*) FILTER (WHERE email = :email AND NOT succeeded) AS by_email,
          count(*) FILTER (WHERE ip = :ip AND NOT succeeded)       AS by_ip
        FROM login_attempts
        WHERE kind = :kind AND attempted_at > now() - interval '15 minutes'
    SQL);
    $stmt->execute(['email' => $email ?? '', 'ip' => $ip, 'kind' => $kind]);
    $row = $stmt->fetch();
    return ((int) $row['by_email']) >= 5 || ((int) $row['by_ip']) >= 20;
}

function record_attempt(PDO $pdo, ?string $email, string $ip, string $kind, bool $succeeded): void
{
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO login_attempts (email, ip, kind, succeeded)
        VALUES (:email, :ip, :kind, :succeeded)
    SQL);
    $stmt->execute([
        'email' => $email, 'ip' => $ip, 'kind' => $kind,
        'succeeded' => $succeeded ? 't' : 'f',
    ]);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Dummy hash precomputed at PASSWORD_DEFAULT's current cost — timing-equalized verify. */
function dummy_password_hash(): string
{
    static $hash = null;
    return $hash ??= password_hash('timing-equalizer-not-a-real-password', PASSWORD_DEFAULT);
}
