<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/practice/session.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

if (practice_state() === null) { header('HX-Location: {"path": "/practice", "target": "#page-content"}'); exit; }

practice_advance();
$s = practice_state();

if (practice_is_finished()) {
    $content = view('practice/summary.php', [
        'examId' => (int) $s['exam_id'], 'examLabel' => (string) $s['exam_label'],
        'total' => practice_total(), 'correct' => (int) $s['correct'],
    ]);
    log_screen_view($pdo, 'practice-summary');
} else {
    $question = find_question($pdo, (int) practice_current_qid());
    $content = view('practice/question.php', [
        'question' => $question, 'index' => (int) $s['pos'], 'total' => practice_total(),
        'score' => (int) $s['correct'], 'answered' => (int) $s['answered'],
        'revealed' => false, 'chosenIds' => [],
    ]);
}
if (is_htmx_request()) { header('Vary: HX-Request'); echo $content; exit; }
echo view('layout.php', ['title' => 'Practice', 'content' => $content, 'active' => 'nav-practice', 'user' => $user]);
