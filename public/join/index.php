<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';

$pdo = db();

log_screen_view($pdo, 'join');

$content = view('play/join-pin.php', ['errors' => [], 'old' => []]);
echo view('play/layout.php', ['title' => 'Join a Game', 'content' => $content]);
