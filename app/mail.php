<?php
declare(strict_types=1);

/**
 * Send one email via MaluMail (malumail-send skill). Returns the decoded API response.
 * Throws RuntimeException on transport errors and non-2xx responses.
 */
function malumail_send(array $mail): array
{
    $ch = curl_init('https://api.malumail.com/v1/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . config('MALUMAIL_API_KEY'),
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($mail, JSON_THROW_ON_ERROR),
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $errno  = curl_errno($ch);
    curl_close($ch);

    if ($errno !== 0 || $body === false) {
        throw new RuntimeException('MaluMail transport error.');
    }
    $decoded = json_decode((string) $body, true);
    if ($status !== 200) {
        throw new RuntimeException("MaluMail send failed ({$status}): " . ($decoded['error'] ?? 'unknown'));
    }
    return $decoded;
}

/**
 * App-level send. Without a MALUMAIL_API_KEY (local dev) the message is written
 * to storage/mail.log instead so reset/verification flows stay testable.
 */
function send_app_mail(string $to, string $subject, string $text, string $html = ''): void
{
    if (config('MALUMAIL_API_KEY') === '') {
        $dir = dirname(__DIR__) . '/storage';
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        file_put_contents(
            $dir . '/mail.log',
            sprintf("[%s] To: %s\nSubject: %s\n%s\n\n", date('c'), $to, $subject, $text),
            FILE_APPEND
        );
        return;
    }
    malumail_send([
        'from'      => config('MAIL_FROM', 'noreply@example.com'),
        'from_name' => config('APP_NAME', 'Claude Games'),
        'to'        => $to,
        'subject'   => $subject,
        'text'      => $text,
        'html'      => $html !== '' ? $html : null,
    ]);
}
