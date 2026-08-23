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

if (abort_game($pdo, $id)) {
    log_activity($pdo, 'game_aborted', [
        'screen' => 'game-host', 'entity' => 'games', 'entity_id' => $id, 'game_id' => $id,
    ]);
}

header('Vary: HX-Request');
$stage = find_host_stage($pdo, $id);
echo view($stage['view'], $stage['data']);
