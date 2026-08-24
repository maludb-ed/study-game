<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';

$user = require_admin();
$pdo  = db();

$id = request_integer('id');
$question = [];
$options = [];
$rationales = [];
$correctIndexes = [];

if ($id !== null) {
    $question = find_question($pdo, $id);
    if ($question === null) {
        http_response_code(404);
        exit('Question not found.');
    }
    foreach ($question['options'] as $option) {
        $options[(int) $option['display_order']]    = $option['option_text'];
        $rationales[(int) $option['display_order']] = $option['rationale'] ?? '';
        if ($option['is_correct']) {
            $correctIndexes[] = (int) $option['display_order'];
        }
    }
}

log_screen_view($pdo, $id === null ? 'question-add' : 'question-edit');
$content = view('questions/partials/form.php', [
    'question'     => $question,
    'options'      => $options,
    'rationales'    => $rationales,
    'correctIndexes' => $correctIndexes,
    'exams'        => find_exams_with_counts($pdo),
    'domains'      => !empty($question['exam_id']) ? find_domains_for_exam($pdo, (int) $question['exam_id']) : [],
    'scenarios'    => !empty($question['exam_id']) ? find_scenarios_for_exam($pdo, (int) $question['exam_id']) : [],
    'errors'       => [],
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
$title = $id === null ? 'Add Question' : 'Edit Question';
$nav   = $id === null ? 'nav-question-add' : 'nav-question-list';
echo view('layout.php', ['title' => $title, 'content' => $content, 'active' => $nav, 'user' => $user]);
