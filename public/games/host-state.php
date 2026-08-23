<?php
declare(strict_types=1);

// The host console's 1s poll: 204 unchanged / current stage / 286 terminal.
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

if (in_array($stage['game']['state'], GAMES_TERMINAL_STATES, true)) {
    http_response_code(286);
    header('Vary: HX-Request');
    echo view($stage['view'], $stage['data']);
    exit;
}

$requestedVersion = (string) ($_GET['v'] ?? '');
if ($stage['version'] === $requestedVersion) {
    http_response_code(204);
    exit;
}

header('Vary: HX-Request');
echo view($stage['view'], $stage['data']);
