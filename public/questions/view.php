<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/questions/queries.php';

$user = require_admin();
$pdo  = db();

$id = request_integer('id');
$question = $id === null ? null : find_question($pdo, $id);
if ($question === null) {
    http_response_code(404);
    exit('Question not found.');
}

log_screen_view($pdo, 'question-view');
$content = view('questions/partials/view.php', ['question' => $question, 'saved' => null]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Question #' . $id, 'content' => $content, 'active' => 'nav-question-list', 'user' => $user]);
