<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

$id   = request_integer('id');
$exam = $id === null ? null : find_exam($pdo, $id);
if ($exam === null) {
    http_response_code(404);
    exit('Exam not found.');
}

log_screen_view($pdo, 'exam-view');
$content = view('exams/partials/coverage.php', [
    'exam'     => $exam,
    'coverage' => find_exam_coverage($pdo, $id, 10),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => $exam['code'], 'content' => $content, 'active' => 'nav-exam-list', 'user' => $user]);
