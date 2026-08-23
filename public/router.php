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
    '/assistant/message'     => '/assistant/message.php',
    '/questions/'               => '/questions/index.php',
    '/questions/new'            => '/questions/form.php',
    '/questions/save'           => '/questions/save.php',
    '/questions/status'         => '/questions/status.php',
    '/questions/delete'         => '/questions/delete.php',
    '/questions/import'         => '/questions/import.php',
    '/questions/domain-options' => '/questions/domain-options.php',
    '/exams/'                   => '/exams/index.php',
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
if (preg_match('#^/exams/(\d+)$#', $path, $m) === 1) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/exams/view.php';
    return true;
}

// Manifest screens whose slice hasn't shipped yet -> stub (values are OUR table,
// not user input; stub.php treats them as trusted).
$stubs = [
    '/games/'           => ['game-list',        'Games',             'slice S2 (Game Nights)',    'nav-game-list'],
    '/games/new'        => ['game-new',         'New Game',          'slice S2 (Game Nights)',    'nav-game-new'],
    '/join'             => ['join',             'Join a Game',       'slice S2 (Game Nights)',    ''],
    '/play'             => ['play',             'Play',              'slice S2 (Game Nights)',    ''],
    '/analytics/'       => ['analytics-group',  'Group Readiness',   'slice S5 (Analytics)',      'nav-analytics-group'],
    '/analytics/drill'  => ['drill-list',       'Drill List',        'slice S5 (Analytics)',      'nav-drill-list'],
    '/ama'              => ['ama',              'Ask Me Anything',   'Phase 4 (the assistant)',   'nav-ama'],
    '/settings/profile' => ['settings-profile', 'Profile',           'a Phase 2 follow-up',       'nav-settings-profile'],
    '/settings/mcp'     => ['settings-mcp',     'MCP Access',        'Phase 4 (MCP servers)',     'nav-settings-mcp'],
];
if (isset($stubs[$path])) {
    [$_GET['screen'], $_GET['title'], $_GET['slice'], $_GET['nav']] = $stubs[$path];
    require __DIR__ . '/stub.php';
    return true;
}

http_response_code(404);
echo '404 — no such screen';
return true;
