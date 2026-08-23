<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';

$user = require_admin();
require_post();
verify_csrf();
$pdo = db();

$id     = request_integer('id');
$status = (string) ($_POST['status'] ?? '');
if ($id === null || !in_array($status, ['draft', 'active', 'retired'], true)) {
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
    set_question_status($pdo, $id, $status);
    log_activity($pdo, 'question_status_changed', [
        'screen' => 'question-view', 'entity' => 'questions', 'entity_id' => $id,
        'before' => ['status' => $before['status']], 'after' => ['status' => $status],
    ]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    throw $exception;
}

if (acting_via_action_token()) {
    action_json(['status' => 'success', 'action' => 'question_status_changed', 'question_id' => $id,
                 'from' => $before['status'], 'to' => $status]);
}
if (is_htmx_request()) {
    header('Vary: HX-Request');
    header('HX-Push-Url: /questions/' . $id);
    echo view('questions/partials/view.php', ['question' => find_question($pdo, $id), 'saved' => null]);
    exit;
}
header('Location: /questions/' . $id);
exit;
