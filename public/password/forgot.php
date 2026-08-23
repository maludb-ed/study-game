<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/auth/queries.php';

auth_page_headers();
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $pdo   = db();

    if (!too_many_attempts($pdo, $email, client_ip(), 'password_reset')) {
        record_attempt($pdo, $email, client_ip(), 'password_reset', true);
        $user = find_user_by_email($pdo, $email);
        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            insert_user_token($pdo, (int) $user['id'], 'password_reset', hash('sha256', $token), '1 hour');
            send_app_mail($email, 'Reset your password',
                "Reset your password (link valid for 1 hour):\n" .
                config('APP_URL') . '/password/reset?token=' . $token);
            log_activity($pdo, 'password_reset_requested', [
                'actor_type' => 'user', 'actor_id' => (int) $user['id'],
                'screen' => 'password-forgot', 'entity' => 'users', 'entity_id' => (int) $user['id'],
            ]);
        }
    }
    $sent = true;   // same message whether or not the account exists
}

$content = view('auth/forgot.php', ['sent' => $sent]);
echo view('auth/layout.php', ['title' => 'Reset password', 'content' => $content]);
