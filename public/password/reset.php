<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/auth/queries.php';

auth_page_headers();
$pdo    = db();
$errors = [];
$token  = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$row    = $token === '' ? null : find_valid_user_token($pdo, 'password_reset', hash('sha256', $token));

if ($row === null) {
    flash_set('warning', 'That reset link is invalid or expired. Request a new one.');
    header('Location: /password/forgot');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    if (mb_strlen($password) < 12) { $errors[] = 'Password must be at least 12 characters.'; }
    if (strlen($password) > 72)    { $errors[] = 'Password must be at most 72 bytes.'; }

    if ($errors === []) {
        consume_user_token($pdo, (int) $row['id']);
        set_user_password($pdo, (int) $row['user_id'], password_hash($password, PASSWORD_DEFAULT));
        mark_email_verified($pdo, (int) $row['user_id']);   // reset proves mailbox control
        log_activity($pdo, 'password_reset_completed', [
            'actor_type' => 'user', 'actor_id' => (int) $row['user_id'],
            'screen' => 'password-reset', 'entity' => 'users', 'entity_id' => (int) $row['user_id'],
        ]);
        flash_set('success', 'Password updated. Log in with your new password.');
        header('Location: /login');
        exit;
    }
}

$content = view('auth/reset.php', ['token' => $token, 'errors' => $errors]);
echo view('auth/layout.php', ['title' => 'Choose a new password', 'content' => $content]);
