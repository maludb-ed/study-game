<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

date_default_timezone_set(config('APP_TZ', 'America/Chicago'));
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/totp.php';

// Session bootstrap per php-session-auth (before session_start()).
$isHttps = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => $isHttps,
    'httponly' => true, 'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('SID');
session_start();
