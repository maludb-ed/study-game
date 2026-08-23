<?php
declare(strict_types=1);

// AMA turn: thin proxy to the unified assistant (surface 'ama'), appends the exchange.
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$question = mb_substr(trim((string) ($_POST['question'] ?? '')), 0, 2000);
if ($question === '') {
    http_response_code(400);
    exit('Ask something.');
}

log_activity($pdo, 'ama_question', [
    'screen' => 'ama', 'details' => ['question' => mb_substr($question, 0, 500)],
]);

$payload = json_encode([
    'surface'      => 'ama',
    'user_id'      => (int) $user['id'],
    'display_name' => $user['display_name'],
    'role'         => $user['role'],
    'message'      => $question,
    'action_token' => mint_action_token((int) $user['id']),
]);
$context = stream_context_create(['http' => [
    'method' => 'POST', 'header' => "Content-Type: application/json\r\n",
    'content' => $payload, 'timeout' => 120, 'ignore_errors' => true,
]]);
$raw = @file_get_contents(rtrim(config('ASSISTANT_URL', 'http://127.0.0.1:8765'), '/') . '/ask', false, $context);
$response = $raw === false ? null : json_decode($raw, true);
$answer = is_array($response) && isset($response['reply'])
    ? (string) $response['reply']
    : 'The assistant is offline — try again in a moment.';

$_SESSION['ama_history'][] = ['question' => $question, 'answer' => $answer];
$_SESSION['ama_history'] = array_slice($_SESSION['ama_history'], -20);

header('Vary: HX-Request');
if (!empty($response['navigate']['path'])) {
    header('HX-Location: ' . json_encode(['path' => (string) $response['navigate']['path'], 'target' => '#page-content'], JSON_UNESCAPED_SLASHES));
}
echo view('ama/partials/exchange.php', ['question' => $question, 'answer' => $answer]);
