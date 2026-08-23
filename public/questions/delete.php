<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';

$user = require_admin();
require_post();
verify_csrf();
$pdo = db();

$id = request_integer('id');
if ($id === null) {
    http_response_code(400);
    exit('Bad request.');
}
$before = find_question($pdo, $id);
if ($before === null) {
    http_response_code(404);
    exit('Question not found.');
}

try {
    $pdo->beginTransaction();
    $deleted = delete_question($pdo, $id);
    if ($deleted) {
        log_activity($pdo, 'question_deleted', [
            'screen' => 'question-view', 'entity' => 'questions', 'entity_id' => $id,
            'before' => array_diff_key($before, ['options' => 1]),
        ]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    throw $exception;
}

if (acting_via_action_token()) {
    action_json($deleted
        ? ['status' => 'success', 'action' => 'question_deleted', 'question_id' => $id]
        : ['status' => 'error', 'errors' => ['Only never-played drafts can be deleted; retire it instead.']]);
}
if (!$deleted) {
    // Only drafts that were never drawn can be deleted; re-render the view with the truth.
    if (is_htmx_request()) {
        header('Vary: HX-Request');
        echo view('questions/partials/view.php', ['question' => find_question($pdo, $id), 'saved' => null]);
        exit;
    }
    header('Location: /questions/' . $id);
    exit;
}

if (is_htmx_request()) {
    header('HX-Location: {"path": "/questions/", "target": "#page-content"}');
    exit;
}
header('Location: /questions/');
exit;
