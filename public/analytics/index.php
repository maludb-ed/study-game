<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/analytics/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

$examId = request_integer('exam_id') ?? find_default_analytics_exam_id($pdo);
$exams  = find_exams_with_counts($pdo);

$domainPerformance = group_domain_performance($pdo, $examId, null);
$tiles             = find_group_tiles($pdo, $examId, $domainPerformance);

$members = find_group_members($pdo, $examId);
foreach ($members as &$member) {
    $readiness = member_readiness($pdo, (int) $member['user_id'], $examId);
    $member['score']       = $readiness['score'];
    $member['band_status'] = $readiness['band_status'];
}
unset($member);

log_screen_view($pdo, 'analytics-group');
$content = view('analytics/group.php', [
    'exams'             => $exams,
    'examId'            => $examId,
    'domainPerformance' => $domainPerformance,
    'tiles'             => $tiles,
    'members'           => $members,
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Group Readiness', 'content' => $content, 'active' => 'nav-analytics-group', 'user' => $user]);
