<?php
declare(strict_types=1);

// The hot path: one player's answer. Public (player_token cookie, no session) —
// CSRF-exempt per the S2 spec amendment 9; validated, idempotent, server-clocked.
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/engine.php';

require_post();
$pdo   = db();
$token = (string) ($_COOKIE['player_token'] ?? '');
$player = $token !== '' ? find_game_player_by_token($pdo, $token) : null;

if ($player === null || $player['kicked_at'] !== null) {
    redirect('/join');
}

$game = find_game($pdo, (int) $player['game_id']);
if ($game === null) {
    redirect('/join');
}

// Selections: option_ids[] for multi-select questions, or a single option_id.
$optionIds = [];
if (isset($_POST['option_ids']) && is_array($_POST['option_ids'])) {
    foreach ($_POST['option_ids'] as $oid) { $optionIds[] = (int) $oid; }
} elseif (($single = request_integer('option_id')) !== null) {
    $optionIds[] = $single;
}
if ($optionIds !== []) {
    insert_answer($pdo, $player, $optionIds);   // late/duplicate/invalid all resolve to the true stage below
}

$stage = find_player_stage($pdo, $player, $game);
if ($stage['terminal']) {
    http_response_code(286);
}
echo view($stage['view'], $stage['data']);
