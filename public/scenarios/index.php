<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

$filters = [
    'exam_id' => request_integer('exam_id') ?? 0,
    'status'  => (string) ($_GET['status'] ?? ''),
    'page'    => request_integer('page') ?? 1,
];
$result = find_scenarios($pdo, $filters['exam_id'], $filters['status'], $filters['page']);

if (is_htmx_request() && ($_SERVER['HTTP_HX_TARGET'] ?? '') === 'scenario-list-results') {
    header('Vary: HX-Request');
    echo view('scenarios/partials/table.php', ['result' => $result, 'filters' => $filters]);
    exit;
}

log_screen_view($pdo, 'scenario-list');
$content = view('scenarios/page.php', [
    'result'  => $result,
    'filters' => $filters,
    'exams'   => find_exams_with_counts($pdo),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Scenarios', 'content' => $content, 'active' => 'nav-scenario-list', 'user' => $user]);
