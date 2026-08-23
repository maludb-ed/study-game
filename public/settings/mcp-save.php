<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/mcp_tokens/queries.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$label  = mb_substr(trim((string) ($_POST['label'] ?? '')), 0, 80);
$server = (string) ($_POST['server'] ?? 'both');

$errors = [];
if (mb_strlen($label) < 3)                                   { $errors[] = 'Give the token a label (3–80 characters).'; }
if (!in_array($server, ['both', 'records', 'activity'], true)) { $errors[] = 'Bad scope.'; }

$createdToken = null;
if ($errors === []) {
    try {
        $pdo->beginTransaction();
        $createdToken = insert_mcp_token($pdo, $label, $server, (int) $user['id']);
        log_activity($pdo, 'mcp_token_created', [
            'screen' => 'settings-mcp', 'entity' => 'mcp',
            'entity_id' => (int) $createdToken['row']['id'],
            'details' => ['label' => $label, 'server' => $server],
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('mcp token create: ' . $exception->getMessage());
        $errors[] = 'The token could not be created.';
    }
}

if (acting_via_action_token()) {
    action_json($errors !== []
        ? ['status' => 'error', 'errors' => $errors]
        : ['status' => 'success', 'action' => 'mcp_token_created',
           'token_plaintext_show_once' => $createdToken['plaintext'], 'label' => $label]);
}
$content = view('settings/mcp.php', [
    'tokens'       => find_mcp_tokens($pdo),
    'createdToken' => $createdToken,
    'errors'       => $errors,
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
