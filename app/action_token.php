<?php
declare(strict_types=1);

/**
 * Short-lived HMAC action tokens (chat-actions): minted by the assistant proxy per
 * turn, accepted by internal endpoints in place of the session so the actions MCP
 * server can act AS the user through the same controllers a human uses.
 * Format: base64url(json{uid, exp}) . '.' . hmac-sha256 over that payload.
 */

const ACTION_TOKEN_TTL_SECONDS = 120;

function mint_action_token(int $userId): string
{
    $payload = rtrim(strtr(base64_encode(json_encode([
        'uid' => $userId,
        'exp' => time() + ACTION_TOKEN_TTL_SECONDS,
    ])), '+/', '-_'), '=');
    return $payload . '.' . hash_hmac('sha256', $payload, config('ACTION_TOKEN_SECRET'));
}

/** Returns the user id, or null when missing/invalid/expired. */
function verify_action_token(string $token): ?int
{
    if ($token === '' || substr_count($token, '.') !== 1 || config('ACTION_TOKEN_SECRET') === '') {
        return null;
    }
    [$payload, $signature] = explode('.', $token, 2);
    if (!hash_equals(hash_hmac('sha256', $payload, config('ACTION_TOKEN_SECRET')), $signature)) {
        return null;
    }
    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')) ?: '', true);
    if (!is_array($data) || ($data['exp'] ?? 0) < time() || ($data['uid'] ?? 0) <= 0) {
        return null;
    }
    return (int) $data['uid'];
}

/** True when this request authenticated via an action token (assistant acting as the user). */
function acting_via_action_token(): bool
{
    return ($GLOBALS['__action_token_user_id'] ?? null) !== null;
}

/** JSON response for action-token callers (the actions MCP server), then exit. */
function action_json(array $payload): never
{
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
