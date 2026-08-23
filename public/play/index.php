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

log_screen_view($pdo, 'play');

if ($player['kicked_at'] !== null) {
    $content = view('play/terminal.php', ['message' => 'Removed by host.']);
} elseif (in_array($game['state'], GAMES_TERMINAL_STATES, true)) {
    $content = view('play/terminal.php', [
        'message' => $game['state'] === 'aborted' ? 'This game was aborted.' : 'This game has ended.',
    ]);
} else {
    $players   = find_game_players($pdo, (int) $game['id']);
    $liveCount = count(array_filter($players, static fn (array $p): bool => $p['kicked_at'] === null));
    $content = view('play/lobby.php', [
        'game'      => $game,
        'player'    => $player,
        'liveCount' => $liveCount,
        'version'   => find_game_version($pdo, (int) $game['id']),
    ]);
}

echo view('play/layout.php', ['title' => 'Play', 'content' => $content]);
