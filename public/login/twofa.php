<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/auth/queries.php';

auth_page_headers();

$pendingId = $_SESSION['pending_2fa_user_id'] ?? null;
$startedAt = $_SESSION['pending_2fa_started_at'] ?? 0;
if (!is_int($pendingId) || (time() - (int) $startedAt) > 600) {
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_started_at']);
    header('Location: /login');
    exit;
}

$pdo   = db();
$user  = find_user($pdo, $pendingId);
$error = null;
if ($user === null || $user['totp_enabled_at'] === null) {
    header('Location: /login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = preg_replace('/\s+/', '', (string) ($_POST['code'] ?? ''));

    if (too_many_attempts($pdo, $user['email'], client_ip(), 'totp')) {
        http_response_code(429);
        $error = 'Too many attempts. Try again later.';
    } else {
        $ok = false;
        if (preg_match('/^\d{6}$/', $code) === 1) {
            $step = totp_verify(totp_decrypt_secret($user['totp_secret']), $code, (int) ($user['totp_last_timestep'] ?? 0));
            if ($step !== null) {
                store_totp_timestep($pdo, $pendingId, $step);
                $ok = true;
            }
        } else {
            foreach (find_unused_recovery_codes($pdo, $pendingId) as $recovery) {
                if (password_verify($code, $recovery['code_hash'])) {
                    mark_recovery_code_used($pdo, (int) $recovery['id']);
                    $ok = true;
                    break;
                }
            }
        }
        record_attempt($pdo, $user['email'], client_ip(), 'totp', $ok);

        if ($ok) {
            establish_session($pendingId);
            log_activity($pdo, 'login_2fa', ['screen' => 'login-2fa']);
            header('Location: /');
            exit;
        }
        $error = 'Invalid code.';
    }
}

$content = view('auth/twofa.php', ['error' => $error]);
echo view('auth/layout.php', ['title' => 'Two-factor authentication', 'content' => $content]);
