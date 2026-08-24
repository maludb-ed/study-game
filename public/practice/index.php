<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_login();
$pdo  = db();
log_screen_view($pdo, 'practice');

$content = view('practice/landing.php', ['exams' => find_exams_with_counts($pdo)]);
if (is_htmx_request()) { header('Vary: HX-Request'); echo $content; exit; }
echo view('layout.php', ['title' => 'Practice', 'content' => $content, 'active' => 'nav-practice', 'user' => $user]);
