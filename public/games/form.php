<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_login();
$pdo  = db();

$questionCount = 10;
$exams = array_values(array_filter(
    find_exams_with_counts($pdo),
    static fn (array $exam): bool => (bool) $exam['is_active']
));

log_screen_view($pdo, 'game-new');
$content = view('games/partials/form.php', [
    'exams'         => $exams,
    'examId'        => 0,
    'questionCount' => $questionCount,
    'seconds'       => 20,
    'streakBonus'   => true,
    'errors'        => [],
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'New Game', 'content' => $content, 'active' => 'nav-game-new', 'user' => $user]);
