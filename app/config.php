<?php
declare(strict_types=1);

// Environment config. Real secrets live in config/env.php (gitignored, see
// config/env.example.php) or the process environment — never in code.
$envFile = dirname(__DIR__) . '/config/env.php';
if (is_file($envFile)) {
    require $envFile;   // calls putenv() for each setting
}

function config(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function is_dev(): bool
{
    return config('APP_ENV', 'dev') === 'dev';
}
