<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/report-queries.php';

$user = require_login();
$pdo  = db();

$id   = request_integer('id');
$game = $id === null ? null : find_game_report($pdo, $id);
if ($game === null) {
    http_response_code(404);
    exit('Game not found.');
}

log_screen_view($pdo, 'game-report');
$content = view('games/report.php', [
    'game'          => $game,
    'questionStats' => find_game_question_stats($pdo, $id),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Game Report', 'content' => $content, 'active' => 'nav-game-list', 'user' => $user]);
