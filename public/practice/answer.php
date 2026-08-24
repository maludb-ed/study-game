<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/practice/session.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$qid = practice_current_qid();
if ($qid === null) { header('HX-Location: {"path": "/practice", "target": "#page-content"}'); exit; }

$question = find_question($pdo, $qid);

$validIds   = array_map(static fn($o) => (int) $o['id'], $question['options']);
$correctIds = [];
foreach ($question['options'] as $opt) {
    if ($opt['is_correct']) { $correctIds[] = (int) $opt['id']; }
}

// Selections arrive as option_ids[] (multi-select) or a single option_id (single-tap).
$selected = [];
if (isset($_POST['option_ids']) && is_array($_POST['option_ids'])) {
    foreach ($_POST['option_ids'] as $oid) { $selected[] = (int) $oid; }
} elseif (($oid = request_integer('option_id')) !== null) {
    $selected[] = (int) $oid;
}
$selected = array_values(array_unique(array_intersect($selected, $validIds)));

// All-or-nothing: the chosen set must exactly equal the correct set.
$a = $selected;   sort($a);
$b = $correctIds; sort($b);
$isCorrect = ($b !== [] && $a === $b);

practice_record($isCorrect);   // idempotent for this cursor
$s = practice_state();

$content = view('practice/question.php', [
    'question' => $question, 'index' => (int) $s['pos'], 'total' => practice_total(),
    'score' => (int) $s['correct'], 'answered' => (int) $s['answered'],
    'revealed' => true, 'chosenIds' => $selected,
]);
if (is_htmx_request()) { header('Vary: HX-Request'); echo $content; exit; }
echo view('layout.php', ['title' => 'Practice', 'content' => $content, 'active' => 'nav-practice', 'user' => $user]);
