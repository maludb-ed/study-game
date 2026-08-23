<?php
declare(strict_types=1);

// Authenticator-app TOTP (RFC 6238, SHA-1, 6 digits, 30s) — no dependency needed.

function totp_generate_secret(): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 32; $i++) {
        $secret .= $alphabet[random_int(0, 31)];
    }
    return $secret;
}

function base32_decode_totp(string $b32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(rtrim($b32, '='));
    $bits = '';
    foreach (str_split($b32) as $c) {
        $v = strpos($alphabet, $c);
        if ($v === false) { throw new InvalidArgumentException('Bad base32.'); }
        $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) { $out .= chr((int) bindec($byte)); }
    }
    return $out;
}

function totp_code(string $secret, int $timestep): string
{
    $key  = base32_decode_totp($secret);
    $data = pack('N*', 0, $timestep);
    $hash = hash_hmac('sha1', $data, $key, true);
    $offset = ord($hash[19]) & 0x0f;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
}

/**
 * Verify with a ±1 timestep window. Returns the accepted timestep (for the
 * replay guard: reject any timestep <= users.totp_last_timestep), or null.
 */
function totp_verify(string $secret, string $code, int $lastTimestep): ?int
{
    $now = intdiv(time(), 30);
    foreach ([$now, $now - 1, $now + 1] as $step) {
        if ($step > $lastTimestep && hash_equals(totp_code($secret, $step), $code)) {
            return $step;
        }
    }
    return null;
}

// Secrets encrypted at rest (libsodium secretbox, key in env — totp-2fa.md).

function totp_encrypt_secret(string $secret): string
{
    $key = hex2bin(config('TOTP_ENC_KEY'));
    if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException('TOTP_ENC_KEY must be 64 hex chars.');
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return base64_encode($nonce . sodium_crypto_secretbox($secret, $nonce, $key));
}

function totp_decrypt_secret(string $stored): string
{
    $key = hex2bin(config('TOTP_ENC_KEY'));
    $raw = base64_decode($stored, true);
    if ($key === false || $raw === false) {
        throw new RuntimeException('TOTP secret decryption failed.');
    }
    $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $box   = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plain = sodium_crypto_secretbox_open($box, $nonce, $key);
    if ($plain === false) {
        throw new RuntimeException('TOTP secret decryption failed.');
    }
    return $plain;
}
