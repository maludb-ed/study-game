<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

log_screen_view($pdo, 'exam-list');
$content = view('exams/page.php', ['exams' => find_exams_with_counts($pdo)]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Exams & Coverage', 'content' => $content, 'active' => 'nav-exam-list', 'user' => $user]);
