<?php
declare(strict_types=1);

// Pattern A fragment: <option> list for the form's domain select.
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';

require_login();
$examId  = request_integer('exam_id') ?? 0;
$domains = $examId > 0 ? find_domains_for_exam(db(), $examId) : [];

header('Vary: HX-Request');
echo '<option value="">' . ($domains === [] ? 'Choose the exam first…' : 'Choose a domain…') . '</option>';
foreach ($domains as $domain) {
    echo '<option value="' . e($domain['id']) . '">' . e($domain['name']) . ' (' . e($domain['weight_pct']) . '%)</option>';
}
