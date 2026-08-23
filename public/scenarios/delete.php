<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';

$user = require_admin();
require_post();
verify_csrf();
$pdo = db();

$id = request_integer('id');
if ($id === null) {
    http_response_code(400);
    exit('Bad request.');
}
$before = find_scenario($pdo, $id);
if ($before === null) {
    http_response_code(404);
    exit('Scenario not found.');
}

try {
    $pdo->beginTransaction();
    $deleted = delete_scenario($pdo, $id);
    if ($deleted) {
        log_activity($pdo, 'scenario_deleted', [
            'screen' => 'scenario-view', 'entity' => 'scenarios', 'entity_id' => $id,
            'before' => array_diff_key($before, ['questions' => 1]),
        ]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    throw $exception;
}

if (!$deleted) {
    if (is_htmx_request()) {
        header('Vary: HX-Request');
        echo view('scenarios/partials/view.php', ['scenario' => find_scenario($pdo, $id), 'saved' => null]);
        exit;
    }
    header('Location: /scenarios/' . $id);
    exit;
}

if (is_htmx_request()) {
    header('HX-Location: {"path": "/scenarios/", "target": "#page-content"}');
    exit;
}
header('Location: /scenarios/');
exit;
