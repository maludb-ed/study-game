<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/mcp_tokens/queries.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$id    = request_integer('id');
$token = $id === null ? null : find_mcp_token($pdo, $id);
if ($token === null) {
    http_response_code(404);
    exit('Token not found.');
}
if ($user['role'] !== 'admin' && (int) $token['created_by'] !== (int) $user['id']) {
    http_response_code(403);
    exit('Forbidden');
}

try {
    $pdo->beginTransaction();
    if (revoke_mcp_token($pdo, $id)) {
        log_activity($pdo, 'mcp_token_revoked', [
            'screen' => 'settings-mcp', 'entity' => 'mcp', 'entity_id' => $id,
            'details' => ['label' => $token['label']],
        ]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    throw $exception;
}

if (acting_via_action_token()) {
    action_json(['status' => 'success', 'action' => 'mcp_token_revoked', 'label' => $token['label']]);
}
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
    header('HX-Push-Url: /settings/mcp');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'MCP Access', 'content' => $content, 'active' => 'nav-settings-mcp', 'user' => $user]);
