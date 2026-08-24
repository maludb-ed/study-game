<?php
declare(strict_types=1);

// Dev router for `php -S localhost:8080 -t public public/router.php`.
// Production maps the same canonical URLs via Apache rewrites (deploy/).
$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Static assets served directly.
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

$routes = [
    '/'                      => '/index.php',
    '/login'                 => '/login.php',
    '/login/2fa'             => '/login/twofa.php',
    '/logout'                => '/logout.php',
    '/register'              => '/register.php',
    '/verify'                => '/verify.php',
    '/password/forgot'       => '/password/forgot.php',
    '/password/reset'        => '/password/reset.php',
    '/auth/google/start'     => '/auth/google/start.php',
    '/auth/google/callback'  => '/auth/google/callback.php',
    '/settings/2fa'          => '/settings/twofa.php',
    '/settings/mcp'          => '/settings/mcp.php',
    '/settings/mcp/save'     => '/settings/mcp-save.php',
    '/settings/mcp/revoke'   => '/settings/mcp-revoke.php',
    '/assistant/message'     => '/assistant/message.php',
    '/assistant/undo'        => '/assistant/undo.php',
    '/ama'                   => '/ama/index.php',
    '/ama/ask'               => '/ama/ask.php',
    '/practice'              => '/practice/index.php',
    '/practice/start'        => '/practice/start.php',
    '/practice/answer'       => '/practice/answer.php',
    '/practice/next'         => '/practice/next.php',
    '/questions/'               => '/questions/index.php',
    '/questions/new'            => '/questions/form.php',
    '/questions/save'           => '/questions/save.php',
    '/questions/status'         => '/questions/status.php',
    '/questions/delete'         => '/questions/delete.php',
    '/questions/import'         => '/questions/import.php',
    '/questions/dependent-fields' => '/questions/dependent-fields.php',
    '/exams/'                   => '/exams/index.php',
    '/scenarios/'               => '/scenarios/index.php',
    '/scenarios/new'            => '/scenarios/form.php',
    '/scenarios/save'           => '/scenarios/save.php',
    '/scenarios/status'         => '/scenarios/status.php',
    '/scenarios/delete'         => '/scenarios/delete.php',
    '/games/'                   => '/games/index.php',
    '/games/new'                => '/games/form.php',
    '/games/save'               => '/games/save.php',
    '/join'                     => '/join/index.php',
    '/join/submit'              => '/join/join.php',
    '/play'                     => '/play/index.php',
    '/play/state'               => '/play/state.php',
    '/play/answer' => '/play/answer.php',
    '/analytics/'                => '/analytics/index.php',
    '/analytics/drill'           => '/analytics/drill.php',
];
if (isset($routes[$path])) {
    require __DIR__ . $routes[$path];
    return true;
}


// Record routes with an id segment.
if (preg_match('#^/questions/(\d+)$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/questions/view.php';
    return true;
}
if (preg_match('#^/questions/(\d+)/edit$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/questions/form.php';
    return true;
}
if (preg_match('#^/scenarios/(\d+)$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/scenarios/view.php';
    return true;
}
if (preg_match('#^/scenarios/(\d+)/edit$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/scenarios/form.php';
    return true;
}
if (preg_match('#^/exams/(\d+)$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/exams/view.php';
    return true;
}
if (preg_match('#^/games/(\d+)$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/games/view.php';
    return true;
}
if (preg_match('#^/games/(\d+)/host$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/games/host.php';
    return true;
}
if (preg_match('#^/games/(\d+)/host-state$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/games/host-state.php';
    return true;
}
if (preg_match('#^/games/(\d+)/advance$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/games/advance.php';
    return true;
}
if (preg_match('#^/games/(\d+)/kick$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/games/kick.php';
    return true;
}
if (preg_match('#^/games/(\d+)/abort$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/games/abort.php';
    return true;
}
if (preg_match('#^/analytics/members/(\d+)$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/analytics/member.php';
    return true;
}

// Manifest screens whose slice hasn't shipped yet -> stub (values are OUR table,
// not user input; stub.php treats them as trusted).
$stubs = [
    '/settings/profile' => ['settings-profile', 'Profile',           'a Phase 2 follow-up',       'nav-settings-profile'],
];
if (isset($stubs[$path])) {
    [$_GET['screen'], $_GET['title'], $_GET['slice'], $_GET['nav']] = $stubs[$path];
    require __DIR__ . '/stub.php';
    return true;
}

http_response_code(404);
echo '404 — no such screen';
return true;
