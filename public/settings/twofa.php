<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/auth/queries.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;

$user = require_login();
$pdo  = db();
$errors = [];
$view   = null;

$full = find_user($pdo, (int) $user['id']);
$isEnabled = $full['totp_enabled_at'] !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $do   = (string) ($_POST['do'] ?? '');
    $code = preg_replace('/\s+/', '', (string) ($_POST['code'] ?? ''));

    if ($do === 'enable' && !$isEnabled) {
        $secret = (string) ($_SESSION['totp_setup_secret'] ?? '');
        $step   = $secret === '' ? null : totp_verify($secret, $code, 0);
        if ($step === null) {
            $errors[] = 'That code didn\'t match. Scan the QR again and enter a fresh code.';
        } else {
            $recoveryCodes = [];
            $hashes = [];
            for ($i = 0; $i < 10; $i++) {
                $plain = strtolower(bin2hex(random_bytes(5)));           // 10 hex chars
                $recoveryCodes[] = substr($plain, 0, 5) . '-' . substr($plain, 5);
                $hashes[] = password_hash(end($recoveryCodes), PASSWORD_DEFAULT);
            }
            try {
                $pdo->beginTransaction();
                enable_user_totp($pdo, (int) $user['id'], totp_encrypt_secret($secret));
                store_totp_timestep($pdo, (int) $user['id'], $step);     // burn the enrollment code
                insert_recovery_codes($pdo, (int) $user['id'], $hashes);
                log_activity($pdo, 'totp_enabled', ['screen' => 'settings-2fa', 'entity' => 'users', 'entity_id' => (int) $user['id']]);
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                throw $exception;
            }
            unset($_SESSION['totp_setup_secret']);
            $view = ['mode' => 'codes', 'recoveryCodes' => $recoveryCodes];
        }
    } elseif ($do === 'disable' && $isEnabled) {
        if (too_many_attempts($pdo, $full['email'], client_ip(), 'totp')) {
            http_response_code(429);
            $errors[] = 'Too many attempts. Try again later.';
        } else {
            $ok = false;
            if (preg_match('/^\d{6}$/', $code) === 1) {
                $step = totp_verify(totp_decrypt_secret($full['totp_secret']), $code, (int) ($full['totp_last_timestep'] ?? 0));
                if ($step !== null) { $ok = true; }
            } else {
                foreach (find_unused_recovery_codes($pdo, (int) $user['id']) as $recovery) {
                    if (password_verify($code, $recovery['code_hash'])) {
                        mark_recovery_code_used($pdo, (int) $recovery['id']);
                        $ok = true;
                        break;
                    }
                }
            }
            record_attempt($pdo, $full['email'], client_ip(), 'totp', $ok);
            if ($ok) {
                disable_user_totp($pdo, (int) $user['id']);
                log_activity($pdo, 'totp_disabled', ['screen' => 'settings-2fa', 'entity' => 'users', 'entity_id' => (int) $user['id']]);
                $isEnabled = false;
                $full['totp_enabled_at'] = null;
            } else {
                $errors[] = 'Invalid code.';
            }
        }
    }
}

log_screen_view($pdo, 'settings-2fa');

if ($view === null) {
    if ($isEnabled) {
        $view = ['mode' => 'enabled', 'enabledAt' => $full['totp_enabled_at']];
    } else {
        // The secret stays in the session, never the database, until confirmed.
        if (empty($_SESSION['totp_setup_secret'])) {
            $_SESSION['totp_setup_secret'] = totp_generate_secret();
        }
        $secret = $_SESSION['totp_setup_secret'];
        $issuer = rawurlencode(config('APP_NAME', 'Cert Arena'));
        $uri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $issuer, rawurlencode($full['email']), $secret, $issuer
        );
        $view = ['mode' => 'setup', 'secret' => $secret, 'qrDataUri' => (new QRCode())->render($uri)];
    }
}

$content = view('settings/twofa.php', $view + ['errors' => $errors]);

if (is_htmx_request()) {
    header('Vary: HX-Request');
    echo $content;
    exit;
}
echo view('layout.php', ['title' => 'Two-Factor Auth', 'content' => $content, 'active' => 'nav-settings-2fa', 'user' => $user]);
