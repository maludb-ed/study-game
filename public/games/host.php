<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';

$user = require_login();
$pdo  = db();

$id   = (int) ($_GET['id'] ?? 0);
$game = find_game($pdo, $id);
if ($game === null) {
    http_response_code(404);
    exit('Game not found.');
}
if ((int) $game['host_user_id'] !== (int) $user['id']) {
    http_response_code(403);
    exit('Forbidden');
}

log_screen_view($pdo, 'game-host');
$content = view('games/partials/host-lobby.php', [
    'game'    => $game,
    'players' => find_game_players($pdo, $id),
    'version' => find_game_version($pdo, $id),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Host Console', 'content' => $content, 'active' => 'nav-game-list', 'user' => $user]);
