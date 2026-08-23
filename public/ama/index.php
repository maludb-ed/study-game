<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

$user = require_login();
$pdo  = db();

log_screen_view($pdo, 'ama');
$content = view('ama/page.php', ['history' => $_SESSION['ama_history'] ?? []]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Ask Me Anything', 'content' => $content, 'active' => 'nav-ama', 'user' => $user]);
