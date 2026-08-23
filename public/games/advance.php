<?php
declare(strict_types=1);

// Host-driven state-machine transition (S3 — replaces the S2 placeholder).
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

$expectedState = (string) ($_POST['expected_state'] ?? '');
$result = advance_game($pdo, $id, (int) $user['id'], $expectedState);

// Double-click safe: a mismatch re-renders the actual current stage with 409
// (the 1s poll also self-heals). Success returns the new stage immediately.
if (acting_via_action_token()) {
    action_json(($result['ok']
        ? ['status' => 'success', 'action' => 'game_advanced']
        : ['status' => 'error', 'errors' => ['expected_state mismatch']])
        + ['state' => $result['state'], 'game_id' => $id]);
}
if (!$result['ok']) {
    http_response_code(409);
}
$stage = find_host_stage($pdo, $id);
header('Vary: HX-Request');
echo view($stage['view'], $stage['data']);
