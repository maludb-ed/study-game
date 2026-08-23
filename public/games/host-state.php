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

$currentVersion   = find_game_version($pdo, $id);
$requestedVersion = (string) ($_GET['v'] ?? '');

if (in_array($game['state'], GAMES_TERMINAL_STATES, true)) {
    http_response_code(286);
    header('Vary: HX-Request');
    echo view('games/partials/host-players.php', [
        'game' => $game, 'players' => find_game_players($pdo, $id), 'version' => $currentVersion,
    ]);
    exit;
}

if ($currentVersion === $requestedVersion) {
    http_response_code(204);
    exit;
}

header('Vary: HX-Request');
echo view('games/partials/host-players.php', [
    'game' => $game, 'players' => find_game_players($pdo, $id), 'version' => $currentVersion,
]);
