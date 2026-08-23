<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/features/auth/queries.php';

auth_page_headers();
$token = (string) ($_GET['token'] ?? '');
$pdo   = db();
$row   = $token === '' ? null : find_valid_user_token($pdo, 'email_verify', hash('sha256', $token));

if ($row !== null) {
    consume_user_token($pdo, (int) $row['id']);
    mark_email_verified($pdo, (int) $row['user_id']);
    log_activity($pdo, 'email_verified', [
        'actor_type' => 'user', 'actor_id' => (int) $row['user_id'],
        'screen' => 'verify', 'entity' => 'users', 'entity_id' => (int) $row['user_id'],
    ]);
    flash_set('success', 'Email verified. Welcome aboard!');
} else {
    flash_set('warning', 'That verification link is invalid or expired.');
}
header('Location: /login');
exit;
