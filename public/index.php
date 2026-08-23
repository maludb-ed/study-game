<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/features/dashboard/queries.php';

$user = require_login();
$pdo  = db();
log_screen_view($pdo, 'dashboard');

$content = view('dashboard/page.php', [
    'stats'    => dashboard_stats($pdo),
    'activity' => recent_activity($pdo),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Dashboard', 'content' => $content, 'active' => 'nav-dashboard', 'user' => $user]);
