<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/engine.php';

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

$playerId = request_integer('player_id');
if ($playerId === null) {
    http_response_code(400);
    exit('Bad request.');
}

$players = find_game_players($pdo, $id);
$target  = null;
foreach ($players as $player) {
    if ((int) $player['id'] === $playerId) {
        $target = $player;
        break;
    }
}

if ($target !== null && kick_game_player($pdo, $id, $playerId)) {
    log_activity($pdo, 'player_kicked', [
        'screen' => 'game-host', 'entity' => 'game_players', 'entity_id' => $playerId, 'game_id' => $id,
        'details' => ['nickname' => $target['nickname']],
    ]);
}

header('Vary: HX-Request');
if (acting_via_action_token()) {
    action_json(['status' => 'success', 'action' => 'player_kicked', 'game_id' => $id]);
}
$stage = find_host_stage($pdo, $id);
echo view($stage['view'], $stage['data']);
