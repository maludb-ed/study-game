<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
require_post();
verify_csrf();
$pdo = db();

$id          = request_integer('id');
$examId      = request_integer('exam_id') ?? 0;
$domainId    = request_integer('domain_id') ?? 0;
$stem        = trim((string) ($_POST['stem'] ?? ''));
$explanation = trim((string) ($_POST['explanation'] ?? ''));
$difficulty  = (string) ($_POST['difficulty'] ?? 'medium');
$status      = (string) ($_POST['status'] ?? 'draft');
$source      = mb_substr(trim((string) ($_POST['source'] ?? '')), 0, 200);
$correct     = request_integer('correct') ?? 0;

$optionInputs = [];
for ($i = 1; $i <= 4; $i++) {
    $text = trim((string) ($_POST['option_' . $i] ?? ''));
    if ($text !== '') {
        $optionInputs[$i] = mb_substr($text, 0, 300);
    }
}

$errors = [];
if ($examId <= 0)                                          { $errors[] = 'Choose an exam.'; }
if ($domainId <= 0)                                        { $errors[] = 'Choose a domain.'; }
if (mb_strlen($stem) < 10 || mb_strlen($stem) > 1000)      { $errors[] = 'The question must be 10–1000 characters.'; }
if (count($optionInputs) < 2)                              { $errors[] = 'Provide at least two options.'; }
if (!isset($optionInputs[$correct]))                       { $errors[] = 'Mark one of the filled options as correct.'; }
if (mb_strlen($explanation) < 10 || mb_strlen($explanation) > 2000) { $errors[] = 'The explanation must be 10–2000 characters.'; }
if (!in_array($difficulty, ['easy', 'medium', 'hard'], true))       { $errors[] = 'Bad difficulty.'; }
if (!in_array($status, ['draft', 'active', 'retired'], true))       { $errors[] = 'Bad status.'; }
if ($examId > 0 && $domainId > 0) {
    $domainOk = false;
    foreach (find_domains_for_exam($pdo, $examId) as $domain) {
        if ((int) $domain['id'] === $domainId) { $domainOk = true; break; }
    }
    if (!$domainOk) { $errors[] = 'That domain does not belong to the chosen exam.'; }
}

if ($errors !== []) {
    $content = view('questions/partials/form.php', [
        'question'     => ['id' => $id, 'exam_id' => $examId, 'domain_id' => $domainId, 'stem' => $stem,
                           'explanation' => $explanation, 'difficulty' => $difficulty, 'status' => $status, 'source' => $source],
        'options'      => $optionInputs,
        'correctIndex' => $correct,
        'exams'        => find_exams_with_counts($pdo),
        'domains'      => $examId > 0 ? find_domains_for_exam($pdo, $examId) : [],
        'errors'       => $errors,
    ]);
    if (is_htmx_request()) {
        header('Vary: HX-Request');
        echo $content;
        exit;
    }
    echo view('layout.php', ['title' => 'Question', 'content' => $content, 'active' => 'nav-question-list', 'user' => $user]);
    exit;
}

$optionRows = [];
foreach ($optionInputs as $position => $text) {
    $optionRows[] = ['text' => $text, 'correct' => $position === $correct];
}

try {
    $pdo->beginTransaction();
    if ($id === null) {
        $question = insert_question($pdo, $examId, $domainId, $stem, $optionRows, $explanation, $difficulty, $status, $source, (int) $user['id']);
        $savedId  = (int) $question['id'];
        log_activity($pdo, 'question_created', [
            'screen' => 'question-add', 'entity' => 'questions', 'entity_id' => $savedId,
            'after'  => $question,
        ]);
        $savedAction = 'created';
    } else {
        $change  = update_question($pdo, $id, $examId, $domainId, $stem, $optionRows, $explanation, $difficulty, $status, $source);
        $savedId = $id;
        log_activity($pdo, 'question_updated', [
            'screen' => 'question-edit', 'entity' => 'questions', 'entity_id' => $id,
            'before' => array_diff_key($change['before'], ['options' => 1]),
            'after'  => $change['after'],
        ]);
        $savedAction = 'updated';
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('question save: ' . $exception->getMessage());
    http_response_code(500);
    exit('The question could not be saved.');
}

if (is_htmx_request()) {
    header('Vary: HX-Request');
    header('HX-Push-Url: /questions/' . $savedId);
    echo view('questions/partials/view.php', ['question' => find_question($pdo, $savedId), 'saved' => $savedAction]);
    exit;
}
header('Location: /questions/' . $savedId);
exit;
