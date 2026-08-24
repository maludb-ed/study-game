<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/practice/session.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$examId = request_integer('exam_id') ?? 0;
$exam   = $examId > 0 ? find_exam($pdo, $examId) : null;
$ids    = $exam ? practice_active_question_ids($pdo, $examId) : [];

if ($exam === null || $ids === []) {
    $content = view('practice/landing.php', ['exams' => find_exams_with_counts($pdo)]);
    if (is_htmx_request()) { header('Vary: HX-Request'); echo $content; exit; }
    echo view('layout.php', ['title' => 'Practice', 'content' => $content, 'active' => 'nav-practice', 'user' => $user]);
    exit;
}

practice_start($examId, $exam['code'] . ' — ' . $exam['name'], $ids);
log_screen_view($pdo, 'practice-run');

$question = find_question($pdo, (int) practice_current_qid());
$content = view('practice/question.php', [
    'question' => $question, 'index' => 0, 'total' => practice_total(),
    'score' => 0, 'answered' => 0, 'revealed' => false, 'chosenIds' => [],
]);
if (is_htmx_request()) { header('Vary: HX-Request'); echo $content; exit; }
echo view('layout.php', ['title' => 'Practice', 'content' => $content, 'active' => 'nav-practice', 'user' => $user]);
