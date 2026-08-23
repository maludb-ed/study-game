<?php
declare(strict_types=1);

/**
 * undo_last (chat-actions): apply the inverse of the user's most recent undoable
 * action, resolved from the activity log. Called by the actions MCP server with an
 * action token (or by the UI with a session).
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/mcp_tokens/queries.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$undoable = [
    'question_created', 'question_updated', 'question_status_changed',
    'scenario_created', 'scenario_updated', 'scenario_status_changed',
    'game_created', 'mcp_token_created',
];
$placeholders = implode(',', array_fill(0, count($undoable), '?'));
$stmt = $pdo->prepare(
    "SELECT * FROM activity_log
     WHERE actor_type = 'user' AND actor_id = ? AND action IN ($placeholders)
       AND occurred_at > now() - interval '1 hour'
     ORDER BY id DESC LIMIT 1"
);
$stmt->execute(array_merge([(int) $user['id']], $undoable));
$last = $stmt->fetch();

if ($last === false) {
    action_json(['status' => 'error', 'errors' => ['Nothing undoable in the last hour.']]);
}

$entityId = (int) $last['entity_id'];
$before   = $last['before_data'] !== null ? json_decode((string) $last['before_data'], true) : null;
$done     = '';

try {
    $pdo->beginTransaction();
    switch ($last['action']) {
        case 'question_created':
            $done = delete_question($pdo, $entityId)
                ? "Deleted question #{$entityId}."
                : (set_question_status($pdo, $entityId, 'retired') ? "Question #{$entityId} was already played — retired it instead." : '');
            break;
        case 'question_updated':
            if (is_array($before)) {
                $pdo->prepare(
                    'UPDATE questions SET exam_id = :exam_id, domain_id = :domain_id, stem = :stem,
                            explanation = :explanation, difficulty = :difficulty, status = :status, source = :source
                     WHERE id = :id'
                )->execute([
                    'id' => $entityId,
                    'exam_id' => $before['exam_id'], 'domain_id' => $before['domain_id'],
                    'stem' => $before['stem'], 'explanation' => $before['explanation'],
                    'difficulty' => $before['difficulty'], 'status' => $before['status'],
                    'source' => $before['source'] ?? '',
                ]);
                $done = "Restored question #{$entityId}'s previous text and settings (options unchanged).";
            }
            break;
        case 'question_status_changed':
            set_question_status($pdo, $entityId, (string) $before['status']);
            $done = "Question #{$entityId} set back to {$before['status']}.";
            break;
        case 'scenario_created':
            $done = delete_scenario($pdo, $entityId)
                ? "Deleted scenario #{$entityId}."
                : (set_scenario_status($pdo, $entityId, 'retired') ? "Scenario #{$entityId} has linked questions — retired it instead." : '');
            break;
        case 'scenario_updated':
            if (is_array($before)) {
                $pdo->prepare(
                    'UPDATE scenarios SET exam_id = :exam_id, title = :title, body = :body, status = :status WHERE id = :id'
                )->execute([
                    'id' => $entityId, 'exam_id' => $before['exam_id'],
                    'title' => $before['title'], 'body' => $before['body'], 'status' => $before['status'],
                ]);
                $done = "Restored scenario #{$entityId}'s previous text and settings.";
            }
            break;
        case 'scenario_status_changed':
            set_scenario_status($pdo, $entityId, (string) $before['status']);
            $done = "Scenario #{$entityId} set back to {$before['status']}.";
            break;
        case 'game_created':
            $done = abort_game($pdo, $entityId) ? "Aborted game #{$entityId}." : "Game #{$entityId} had already ended.";
            break;
        case 'mcp_token_created':
            $done = revoke_mcp_token($pdo, $entityId) ? 'Revoked the token.' : 'That token was already revoked.';
            break;
    }
    log_activity($pdo, 'action_undone', [
        'screen' => 'assistant', 'entity' => (string) $last['entity'], 'entity_id' => $entityId,
        'details' => ['undid' => $last['action'], 'result' => $done],
    ]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('undo: ' . $exception->getMessage());
    action_json(['status' => 'error', 'errors' => ['The undo failed: ' . $exception->getMessage()]]);
}

action_json(['status' => $done === '' ? 'error' : 'success',
             'undid' => $last['action'], 'result' => $done !== '' ? $done : 'Nothing to undo.']);
