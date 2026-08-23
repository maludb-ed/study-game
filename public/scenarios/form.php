<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

$id = request_integer('id');
$scenario = [];
if ($id !== null) {
    $scenario = find_scenario($pdo, $id);
    if ($scenario === null) {
        http_response_code(404);
        exit('Scenario not found.');
    }
}

log_screen_view($pdo, $id === null ? 'scenario-add' : 'scenario-edit');
$content = view('scenarios/partials/form.php', [
    'scenario' => $scenario,
    'exams'    => find_exams_with_counts($pdo),
    'errors'   => [],
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => $id === null ? 'Add Scenario' : 'Edit Scenario', 'content' => $content, 'active' => 'nav-scenario-list', 'user' => $user]);
