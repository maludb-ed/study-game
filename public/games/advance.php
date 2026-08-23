<?php
declare(strict_types=1);

// S2 PLACEHOLDER — the live game loop (lobby -> question -> ... ) ships with the
// game-loop slice (S3). This endpoint validates the request and re-renders the
// host lobby stage with a notice; it never changes games.state.

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

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

header('Vary: HX-Request');
echo view('games/partials/host-players.php', [
    'game'    => $game,
    'players' => find_game_players($pdo, $id),
    'version' => find_game_version($pdo, $id),
    'notice'  => 'The live game loop ships with the game-loop slice.',
]);
