<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/analytics/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_admin();
$pdo  = db();

$id     = request_integer('id');
$member = $id === null ? null : find_user($pdo, $id);
if ($member === null) {
    http_response_code(404);
    exit('Member not found.');
}

$examId = request_integer('exam_id') ?? find_default_analytics_exam_id($pdo);

log_screen_view($pdo, 'analytics-member');
$content = view('analytics/member.php', [
    'member'             => $member,
    'examId'             => $examId,
    'exams'              => find_exams_with_counts($pdo),
    'readiness'          => member_readiness($pdo, $id, $examId),
    'examsPlayedCount'   => count(find_member_exams_played($pdo, $id)),
    'claimedAnswerCount' => count_member_claimed_answers($pdo, $id),
    'unclaimedCount'     => count_unclaimed_game_players($pdo),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => $member['display_name'] . ' — Readiness', 'content' => $content, 'active' => 'nav-analytics-group', 'user' => $user]);
