<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
require_post();
verify_csrf();
$pdo = db();

$id     = request_integer('id');
$examId = request_integer('exam_id') ?? 0;
$title  = trim((string) ($_POST['title'] ?? ''));
$body   = trim((string) ($_POST['body'] ?? ''));
$status = (string) ($_POST['status'] ?? 'draft');

$errors = [];
if ($examId <= 0)                                        { $errors[] = 'Choose an exam.'; }
if (mb_strlen($title) < 5 || mb_strlen($title) > 120)    { $errors[] = 'The title must be 5–120 characters.'; }
if (mb_strlen($body) < 50 || mb_strlen($body) > 4000)    { $errors[] = 'The scenario must be 50–4000 characters.'; }
if (!in_array($status, ['draft', 'active', 'retired'], true)) { $errors[] = 'Bad status.'; }

if ($errors !== []) {
    if (acting_via_action_token()) {
        action_json(['status' => 'error', 'errors' => $errors]);
    }
    $content = view('scenarios/partials/form.php', [
        'scenario' => ['id' => $id, 'exam_id' => $examId, 'title' => $title, 'body' => $body, 'status' => $status],
        'exams'    => find_exams_with_counts($pdo),
        'errors'   => $errors,
    ]);
    if (is_htmx_request()) {
        header('Vary: HX-Request');
        echo $content;
        exit;
    }
    echo view('layout.php', ['title' => 'Scenario', 'content' => $content, 'active' => 'nav-scenario-list', 'user' => $user]);
    exit;
}

try {
    $pdo->beginTransaction();
    if ($id === null) {
        $scenario = insert_scenario($pdo, $examId, $title, $body, $status, (int) $user['id']);
        $savedId  = (int) $scenario['id'];
        log_activity($pdo, 'scenario_created', [
            'screen' => 'scenario-add', 'entity' => 'scenarios', 'entity_id' => $savedId, 'after' => $scenario,
        ]);
        $savedAction = 'created';
    } else {
        $change  = update_scenario($pdo, $id, $examId, $title, $body, $status);
        $savedId = $id;
        log_activity($pdo, 'scenario_updated', [
            'screen' => 'scenario-edit', 'entity' => 'scenarios', 'entity_id' => $id,
            'before' => array_diff_key($change['before'], ['questions' => 1]), 'after' => $change['after'],
        ]);
        $savedAction = 'updated';
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('scenario save: ' . $exception->getMessage());
    http_response_code(500);
    exit('The scenario could not be saved.');
}

if (acting_via_action_token()) {
    action_json(['status' => 'success', 'action' => 'scenario_' . $savedAction, 'scenario_id' => $savedId,
                 'url' => '/scenarios/' . $savedId]);
}
if (is_htmx_request()) {
    header('Vary: HX-Request');
    header('HX-Push-Url: /scenarios/' . $savedId);
    echo view('scenarios/partials/view.php', ['scenario' => find_scenario($pdo, $savedId), 'saved' => $savedAction]);
    exit;
}
header('Location: /scenarios/' . $savedId);
exit;
