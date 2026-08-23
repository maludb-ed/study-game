<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_login();
$pdo  = db();

$filters = [
    'exam_id' => request_integer('exam_id') ?? 0,
    'page'    => request_integer('page') ?? 1,
];

$result = find_games($pdo, $filters['exam_id'], $filters['page']);

// Sub-swap (exam filter / pagination) refreshes only the results region.
if (is_htmx_request() && ($_SERVER['HTTP_HX_TARGET'] ?? '') === 'game-list-results') {
    header('Vary: HX-Request');
    echo view('games/partials/table.php', ['result' => $result, 'filters' => $filters]);
    exit;
}

log_screen_view($pdo, 'game-list');
$content = view('games/page.php', [
    'result'  => $result,
    'filters' => $filters,
    'exams'   => find_exams_with_counts($pdo),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Games', 'content' => $content, 'active' => 'nav-game-list', 'user' => $user]);
