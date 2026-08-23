<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/mcp_tokens/queries.php';

$user = require_login();
$pdo  = db();

log_screen_view($pdo, 'settings-mcp');
$content = view('settings/mcp.php', [
    'tokens'       => find_mcp_tokens($pdo),
    'createdToken' => null,
    'errors'       => [],
    'user'         => $user,
    'recordsUrl'   => config('PUBLIC_RECORDS_MCP_URL', config('APP_URL') . '/mcp/records'),
    'activityUrl'  => config('PUBLIC_ACTIVITY_MCP_URL', config('APP_URL') . '/mcp/activity'),
]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'MCP Access', 'content' => $content, 'active' => 'nav-settings-mcp', 'user' => $user]);
