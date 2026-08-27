<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/scenarios/queries.php';

$user = require_admin();
$pdo  = db();

$id = request_integer('id');
$scenario = $id === null ? null : find_scenario($pdo, $id);
if ($scenario === null) {
    http_response_code(404);
    exit('Scenario not found.');
}

log_screen_view($pdo, 'scenario-view');
$content = view('scenarios/partials/view.php', ['scenario' => $scenario, 'saved' => null]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Scenario #' . $id, 'content' => $content, 'active' => 'nav-scenario-list', 'user' => $user]);
