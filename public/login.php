<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/features/auth/queries.php';

auth_page_headers();
if (current_user() !== null) {
    header('Location: /');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $pdo      = db();

    if (too_many_attempts($pdo, $email, client_ip(), 'password')) {
        http_response_code(429);
        $error = 'Too many attempts. Try again later.';
    } else {
        $user = find_user_by_email($pdo, $email);
        // Timing-equalized: verify against a dummy hash when the user or hash is missing.
        $ok = password_verify($password, $user['password_hash'] ?? dummy_password_hash())
            && $user !== null && $user['password_hash'] !== null;
        record_attempt($pdo, $email, client_ip(), 'password', $ok);

        if ($ok) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                set_user_password($pdo, (int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
            }
            if ($user['totp_enabled_at'] !== null) {
                session_regenerate_id(true);
                $_SESSION['pending_2fa_user_id']   = (int) $user['id'];
                $_SESSION['pending_2fa_started_at'] = time();
                header('Location: /login/2fa');
                exit;
            }
            establish_session((int) $user['id']);
            log_activity($pdo, 'login', ['screen' => 'login', 'details' => ['method' => 'password']]);
            header('Location: /');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}

$content = view('auth/login.php', [
    'error'         => $error,
    'flash'         => flash_take(),
    'googleEnabled' => config('GOOGLE_CLIENT_ID') !== '',
]);
echo view('auth/layout.php', ['title' => 'Login', 'content' => $content]);
