<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/engine.php';

$user = require_login();
$pdo  = db();

$id    = (int) ($_GET['id'] ?? 0);
$stage = find_host_stage($pdo, $id);
if ($stage === null) {
    http_response_code(404);
    exit('Game not found.');
}
if ((int) $stage['game']['host_user_id'] !== (int) $user['id']) {
    http_response_code(403);
    exit('Forbidden');
}

log_screen_view($pdo, 'game-host');
$stageHtml = view($stage['view'], $stage['data']);
$content   = view('games/partials/host-lobby.php', ['game' => $stage['game'], 'stageHtml' => $stageHtml]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Host Console', 'content' => $content, 'active' => 'nav-game-list', 'user' => $user]);
