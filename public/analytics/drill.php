<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/analytics/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';

$user = require_login();
$pdo  = db();

$filters = [
    'exam_id'   => request_integer('exam_id') ?? 0,
    'domain_id' => request_integer('domain_id') ?? 0,
    'page'      => request_integer('page') ?? 1,
];

$result = most_missed_questions($pdo, $filters['exam_id'], $filters['domain_id'], $filters['page']);

// Sub-swap (domain filter / pagination) refreshes only the results region.
if (is_htmx_request() && ($_SERVER['HTTP_HX_TARGET'] ?? '') === 'drill-list-table') {
    header('Vary: HX-Request');
    echo view('analytics/drill.php', ['result' => $result, 'filters' => $filters, 'resultsOnly' => true]);
    exit;
}

log_screen_view($pdo, 'drill-list');
$content = view('analytics/drill.php', [
    'result'      => $result,
    'filters'     => $filters,
    'exams'       => find_exams_with_counts($pdo),
    'domains'     => $filters['exam_id'] > 0 ? find_domains_for_exam($pdo, $filters['exam_id']) : [],
    'resultsOnly' => false,
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Drill List', 'content' => $content, 'active' => 'nav-drill-list', 'user' => $user]);
