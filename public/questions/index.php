<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

$filters = [
    'exam_id'   => request_integer('exam_id') ?? 0,
    'domain_id' => request_integer('domain_id') ?? 0,
    'status'    => (string) ($_GET['status'] ?? ''),
    'q'         => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100),
    'sort'      => (string) ($_GET['sort'] ?? 'updated_at'),
    'page'      => request_integer('page') ?? 1,
];

$result = find_questions(
    $pdo, $filters['exam_id'], $filters['domain_id'],
    $filters['status'], $filters['q'], $filters['sort'], $filters['page']
);

// Sub-swap (search/pagination/filter) refreshes only the results region.
if (is_htmx_request() && ($_SERVER['HTTP_HX_TARGET'] ?? '') === 'question-list-results') {
    header('Vary: HX-Request');
    echo view('questions/partials/table.php', ['result' => $result, 'filters' => $filters, 'isAdmin' => $user['role'] === 'admin']);
    exit;
}

log_screen_view($pdo, 'question-list');
$content = view('questions/page.php', [
    'result'  => $result,
    'filters' => $filters,
    'isAdmin' => $user['role'] === 'admin',
    'exams'   => find_exams_with_counts($pdo),
    'domains' => $filters['exam_id'] > 0 ? find_domains_for_exam($pdo, $filters['exam_id']) : [],
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Questions', 'content' => $content, 'active' => 'nav-question-list', 'user' => $user]);
