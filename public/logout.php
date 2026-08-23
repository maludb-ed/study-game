<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Logout must be POST + CSRF-guarded (a GET logout link is a CSRF vector).
require_post();
verify_csrf();

if (current_user() !== null) {
    log_activity(db(), 'logout', ['screen' => 'logout']);
}
$_SESSION = [];
session_regenerate_id(true);
session_destroy();
header('Location: /login');
exit;
