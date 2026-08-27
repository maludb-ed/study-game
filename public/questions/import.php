<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

$json     = '';
$report   = null;
$imported = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $json = (string) ($_POST['json'] ?? '');
    $do   = (string) ($_POST['do'] ?? 'validate');

    // Resolve exams + domains once for name matching.
    $examsByCode = [];
    foreach (find_exams_with_counts($pdo) as $exam) {
        $examsByCode[$exam['code']] = ['id' => (int) $exam['id'], 'domains' => []];
        foreach (find_domains_for_exam($pdo, (int) $exam['id']) as $domain) {
            $examsByCode[$exam['code']]['domains'][mb_strtolower($domain['name'])] = (int) $domain['id'];
        }
    }

    $items  = json_decode($json, true);
    $report = [];
    $parsed = [];
    if (!is_array($items) || $items === [] || array_is_list($items) === false) {
        $report[] = ['index' => 0, 'errors' => ['The payload must be a non-empty JSON array of question objects.']];
    } else {
        foreach ($items as $index => $item) {
            $itemErrors = [];
            $examCode = (string) ($item['exam_code'] ?? '');
            $domain   = mb_strtolower(trim((string) ($item['domain'] ?? '')));
            $stem     = trim((string) ($item['stem'] ?? ''));
            $explanation = trim((string) ($item['explanation'] ?? ''));
            $difficulty  = (string) ($item['difficulty'] ?? 'medium');
            $source      = mb_substr(trim((string) ($item['source'] ?? '')), 0, 150);
            $options     = $item['options'] ?? [];

            if (!isset($examsByCode[$examCode])) { $itemErrors[] = "Unknown exam_code '{$examCode}'."; }
            elseif (!isset($examsByCode[$examCode]['domains'][$domain])) { $itemErrors[] = "Domain does not match the exam blueprint."; }
            if (mb_strlen($stem) < 10 || mb_strlen($stem) > 1000)              { $itemErrors[] = 'Stem must be 10–1000 chars.'; }
            if (mb_strlen($explanation) < 10 || mb_strlen($explanation) > 4000) { $itemErrors[] = 'Explanation must be 10–4000 chars.'; }
            if (!in_array($difficulty, ['easy', 'medium', 'hard'], true))       { $itemErrors[] = 'Bad difficulty.'; }
            $optionRows = [];
            $correctCount = 0;
            if (!is_array($options) || count($options) < 2 || count($options) > 6) {
                $itemErrors[] = 'Provide 2–6 options.';
            } else {
                foreach ($options as $option) {
                    $text = trim((string) ($option['text'] ?? ''));
                    $isCorrect = ($option['correct'] ?? false) === true;
                    $rationale = mb_substr(trim((string) ($option['rationale'] ?? '')), 0, 500);
                    if ($text === '' || mb_strlen($text) > 300) { $itemErrors[] = 'Every option needs text (max 300 chars).'; break; }
                    if ($isCorrect) { $correctCount++; }
                    $optionRows[] = ['text' => $text, 'correct' => $isCorrect, 'rationale' => $rationale];
                }
                if ($correctCount < 1) { $itemErrors[] = 'At least one option must be marked correct.'; }
            }

            if ($itemErrors !== []) {
                $report[] = ['index' => $index + 1, 'errors' => $itemErrors];
            } else {
                $parsed[] = [
                    'exam_id'     => $examsByCode[$examCode]['id'],
                    'domain_id'   => $examsByCode[$examCode]['domains'][$domain],
                    'stem'        => $stem,
                    'options'     => $optionRows,
                    'explanation' => $explanation,
                    'difficulty'  => $difficulty,
                    'source'      => trim('import ' . date('Y-m-d') . ': ' . $source),
                ];
            }
        }
    }

    if ($do === 'import' && $report === [] && $parsed !== []) {
        try {
            $pdo->beginTransaction();
            foreach ($parsed as $item) {
                insert_question(
                    $pdo, $item['exam_id'], $item['domain_id'], $item['stem'], $item['options'],
                    $item['explanation'], $item['difficulty'], 'draft', $item['source'], (int) $user['id']
                );
            }
            log_activity($pdo, 'questions_imported', [
                'screen' => 'question-import', 'entity' => 'questions',
                'details' => ['count' => count($parsed)],
            ]);
            $pdo->commit();
            $imported = count($parsed);
            $report   = null;
            $json     = '';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('question import: ' . $exception->getMessage());
            $report = [['index' => 0, 'errors' => ['Import failed — nothing was saved.']]];
        }
    }

    if (is_htmx_request()) {
        header('Vary: HX-Request');
        echo view('questions/partials/import-report.php', ['report' => $report, 'imported' => $imported]);
        exit;
    }
}

log_screen_view($pdo, 'question-import');
$content = view('questions/partials/import.php', ['json' => $json, 'report' => $report, 'imported' => $imported]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Import Questions', 'content' => $content, 'active' => 'nav-question-import', 'user' => $user]);
