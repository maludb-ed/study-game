<?php
declare(strict_types=1);

// Placeholder endpoint for manifest screens whose slice hasn't shipped yet.
// The router passes ?screen=…&title=…&slice=…&nav=… (trusted: values come from
// the router's own table, never from user input).
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user   = require_login();
$screen = (string) ($_GET['screen'] ?? 'stub');
$title  = (string) ($_GET['title'] ?? 'Coming soon');
$slice  = (string) ($_GET['slice'] ?? 'a later slice');
$nav    = (string) ($_GET['nav'] ?? '');

log_screen_view(db(), $screen);

$content = view('shared/stub.php', ['screen' => $screen, 'screenTitle' => $title, 'slice' => $slice]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => $title, 'content' => $content, 'active' => $nav, 'user' => $user]);
