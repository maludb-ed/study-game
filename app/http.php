<?php
declare(strict_types=1);

function is_htmx_request(): bool
{
    return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Render a view located under app/views.
 * Template names must be supplied by application code, never from request parameters.
 */
function view(string $template, array $data = []): string
{
    $viewsRoot = realpath(__DIR__ . '/views');
    if ($viewsRoot === false) {
        throw new RuntimeException('View directory does not exist.');
    }
    $path = realpath($viewsRoot . '/' . ltrim($template, '/'));
    if ($path === false || !str_starts_with($path, $viewsRoot . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException("Invalid view: {$template}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    try {
        require $path;
        return (string) ob_get_clean();
    } catch (Throwable $exception) {
        ob_end_clean();
        throw $exception;
    }
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }
}

function request_integer(string $name): ?int
{
    $value = $_POST[$name] ?? $_GET[$name] ?? null;
    if ($value === null || $value === '') {
        return null;
    }
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    return $filtered === false ? null : $filtered;
}

/** Full navigation for browsers, HX-Redirect for HTMX — used by auth gates. */
function redirect(string $url): never
{
    if (is_htmx_request()) {
        header('HX-Redirect: ' . $url);
    } else {
        header('Location: ' . $url);
    }
    exit;
}

/** Security headers for auth pages (login, register, reset, 2FA). */
function auth_page_headers(): void
{
    header('Cache-Control: no-store');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}

/** Format a timestamptz for table cells: Jan 15, 2026 (design-decisions.md). */
function fmt_date(?string $ts): string
{
    if ($ts === null || $ts === '') { return ''; }
    return date('M j, Y', strtotime($ts));
}

function fmt_datetime(?string $ts): string
{
    if ($ts === null || $ts === '') { return ''; }
    return date('M j, Y g:i A', strtotime($ts));
}

// --- Flash messages (one-shot, session-backed) ----------------------------

function flash_set(string $kind, string $message): void
{
    $_SESSION['flash'] = ['kind' => $kind, 'message' => $message];
}

function flash_take(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
