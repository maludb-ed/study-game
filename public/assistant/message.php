<?php
declare(strict_types=1);

/**
 * Command-bar handler (chat-actions Phase 4): session auth + CSRF, activity log,
 * mint an action token, proxy to the unified assistant service, translate its
 * outcome into HTMX responses (HX-Location for navigation, HX-Trigger for data).
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$message  = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000);
$screen   = mb_substr(trim((string) ($_POST['screen'] ?? '')), 0, 80);
$entity   = mb_substr(trim((string) ($_POST['entity'] ?? '')), 0, 80);
$recordId = request_integer('record_id');

if ($message === '') {
    header('Vary: HX-Request');
    echo '<span id="assistant-reply-text" class="text-muted">Say or type what you want — e.g. "go to the drill list" or "start a CCAO-F game".</span>';
    exit;
}

log_activity($pdo, 'assistant_message', [
    'screen' => $screen, 'details' => ['utterance' => mb_substr($message, 0, 500)],
]);

$payload = json_encode([
    'surface'      => 'command_bar',
    'user_id'      => (int) $user['id'],
    'display_name' => $user['display_name'],
    'role'         => $user['role'],
    'message'      => $message,
    'screen'       => $screen,
    'entity'       => $entity,
    'record_id'    => $recordId,
    'action_token' => mint_action_token((int) $user['id']),
]);

$context = stream_context_create(['http' => [
    'method' => 'POST', 'header' => "Content-Type: application/json\r\n",
    'content' => $payload, 'timeout' => 60, 'ignore_errors' => true,
]]);
$raw = @file_get_contents(rtrim(config('ASSISTANT_URL', 'http://127.0.0.1:8765'), '/') . '/message', false, $context);
$response = $raw === false ? null : json_decode($raw, true);

header('Vary: HX-Request');
if (!is_array($response) || !isset($response['reply'])) {
    echo '<span id="assistant-reply-text" class="text-danger"><i class="feather-alert-triangle me-1"></i>The assistant is offline — use the sidebar for now.</span>';
    exit;
}

if (!empty($response['navigate']['path'])) {
    header('HX-Location: ' . json_encode([
        'path'   => (string) $response['navigate']['path'],
        'target' => '#page-content',
    ], JSON_UNESCAPED_SLASHES));
}
if (!empty($response['trigger'])) {
    header('HX-Trigger: ' . (string) $response['trigger']);
}
echo '<span id="assistant-reply-text">' . e((string) $response['reply']) . '</span>';
