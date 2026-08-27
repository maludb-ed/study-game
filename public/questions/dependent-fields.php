<?php
declare(strict_types=1);

// Pattern A fragment (S6, replaces domain-options.php): the question form's
// exam-dependent field rows — domain select + scenario select — for a chosen exam.
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';

require_admin();
$examId    = request_integer('exam_id') ?? 0;
$domains   = $examId > 0 ? find_domains_for_exam(db(), $examId) : [];
$scenarios = $examId > 0 ? find_scenarios_for_exam(db(), $examId) : [];

header('Vary: HX-Request');
echo view('questions/partials/dependent-fields.php', [
    'domains'          => $domains,
    'scenarios'        => $scenarios,
    'domainSelected'   => 0,
    'scenarioSelected' => 0,
]);
