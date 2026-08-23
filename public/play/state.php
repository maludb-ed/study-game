<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';

$pdo   = db();
$token = (string) ($_COOKIE['player_token'] ?? '');
$player = $token !== '' ? find_game_player_by_token($pdo, $token) : null;

if ($player === null) {
    redirect('/join');
}

$game = find_game($pdo, (int) $player['game_id']);
if ($game === null) {
    redirect('/join');
}

if ($player['kicked_at'] !== null) {
    http_response_code(286);
    echo view('play/terminal.php', ['message' => 'Removed by host.']);
    exit;
}

if (in_array($game['state'], GAMES_TERMINAL_STATES, true)) {
    http_response_code(286);
    echo view('play/terminal.php', [
        'message' => $game['state'] === 'aborted' ? 'This game was aborted.' : 'This game has ended.',
    ]);
    exit;
}

$currentVersion   = find_game_version($pdo, (int) $game['id']);
$requestedVersion = (string) ($_GET['v'] ?? '');

if ($currentVersion === $requestedVersion) {
    http_response_code(204);
    exit;
}

$players   = find_game_players($pdo, (int) $game['id']);
$liveCount = count(array_filter($players, static fn (array $p): bool => $p['kicked_at'] === null));
echo view('play/lobby.php', [
    'game' => $game, 'player' => $player, 'liveCount' => $liveCount, 'version' => $currentVersion,
]);
