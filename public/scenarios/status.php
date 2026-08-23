<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';

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
$before = find_scenario($pdo, $id);
if ($before === null) {
    http_response_code(404);
    exit('Scenario not found.');
}

try {
    $pdo->beginTransaction();
    set_scenario_status($pdo, $id, $status);
    log_activity($pdo, 'scenario_status_changed', [
        'screen' => 'scenario-view', 'entity' => 'scenarios', 'entity_id' => $id,
        'before' => ['status' => $before['status']], 'after' => ['status' => $status],
    ]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    throw $exception;
}

if (is_htmx_request()) {
    header('Vary: HX-Request');
    header('HX-Push-Url: /scenarios/' . $id);
    echo view('scenarios/partials/view.php', ['scenario' => find_scenario($pdo, $id), 'saved' => null]);
    exit;
}
header('Location: /scenarios/' . $id);
exit;
