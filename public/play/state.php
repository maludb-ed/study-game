<?php
declare(strict_types=1);

// The player's poll: 204 unchanged / current stage / 286 terminal (kicked, over, podium).
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/engine.php';

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

$stage = find_player_stage($pdo, $player, $game);

if ($stage['terminal']) {
    http_response_code(286);
    echo view($stage['view'], $stage['data']);
    exit;
}

$requestedVersion = (string) ($_GET['v'] ?? '');
if (($stage['data']['version'] ?? '') === $requestedVersion) {
    http_response_code(204);
    exit;
}

echo view($stage['view'], $stage['data']);
