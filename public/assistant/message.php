<?php
declare(strict_types=1);

// Command-bar stub handler (chat-actions Phase 2). The real unified assistant
// service replaces the reply logic in Phase 4; the surface and logging exist now.
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

require_login();
require_post();
verify_csrf();

$message = trim((string) ($_POST['message'] ?? ''));
$screen  = trim((string) ($_POST['screen'] ?? ''));

if ($message !== '') {
    log_activity(db(), 'assistant_message', [
        'screen'  => $screen,
        'details' => ['utterance' => mb_substr($message, 0, 500), 'handler' => 'stub'],
    ]);
}

header('Vary: HX-Request');
echo '<span id="assistant-reply-text"><i class="feather-info me-1"></i>'
   . 'Heard: &ldquo;' . e(mb_substr($message, 0, 120)) . '&rdquo; — the assistant comes online in Phase 4. '
   . 'Until then, use the sidebar.</span>';
