<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/features/auth/queries.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

auth_page_headers();
$pdo = db();

$fail = function (string $logDetail) use ($pdo): never {
    record_attempt($pdo, null, client_ip(), 'google', false);
    log_activity($pdo, 'google_signin_failed', ['screen' => 'login', 'details' => ['reason' => $logDetail]]);
    flash_set('danger', 'Sign-in failed. Try again or use your password.');
    header('Location: /login');
    exit;
};

if (too_many_attempts($pdo, null, client_ip(), 'google')) {
    http_response_code(429);
    exit('Too many attempts. Try again later.');
}

// Consume state/verifier on first use so a replayed callback fails.
$expectedState = (string) ($_SESSION['oauth2state'] ?? '');
$pkceVerifier  = $_SESSION['oauth2pkceVerifier'] ?? null;
$nonce         = (string) ($_SESSION['oauth_nonce'] ?? '');
unset($_SESSION['oauth2state'], $_SESSION['oauth2pkceVerifier'], $_SESSION['oauth_nonce']);

$state = (string) ($_GET['state'] ?? '');
$code  = (string) ($_GET['code'] ?? '');
if ($expectedState === '' || $state === '' || !hash_equals($expectedState, $state) || $code === '') {
    $fail('state_mismatch');
}

$provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => config('GOOGLE_CLIENT_ID'),
    'clientSecret' => config('GOOGLE_CLIENT_SECRET'),
    'redirectUri'  => config('APP_URL') . '/auth/google/callback',
]);

try {
    if ($pkceVerifier !== null && method_exists($provider, 'setPkceCode')) {
        $provider->setPkceCode($pkceVerifier);
    }
    $token  = $provider->getAccessToken('authorization_code', ['code' => $code]);
    $values = $token->getValues();
    $idToken = (string) ($values['id_token'] ?? '');
    // The league provider validated the token exchange over TLS with the client
    // secret; decode the ID token payload for claims and enforce iss/aud/exp/nonce.
    $parts = explode('.', $idToken);
    if (count($parts) !== 3) { $fail('bad_id_token'); }
    $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?: [];

    $issOk   = in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
    $audOk   = ($claims['aud'] ?? '') === config('GOOGLE_CLIENT_ID');
    $expOk   = ((int) ($claims['exp'] ?? 0)) > time();
    $nonceOk = $nonce !== '' && hash_equals($nonce, (string) ($claims['nonce'] ?? ''));
    if (!$issOk || !$audOk || !$expOk || !$nonceOk) { $fail('claim_validation'); }
    if (($claims['email_verified'] ?? false) !== true) { $fail('email_unverified'); }

    $sub   = (string) ($claims['sub'] ?? '');
    $email = strtolower(trim((string) ($claims['email'] ?? '')));
    $name  = trim((string) ($claims['name'] ?? '')) ?: explode('@', $email)[0];
    if ($sub === '' || $email === '') { $fail('missing_claims'); }
} catch (Throwable $exception) {
    error_log('google oauth: ' . $exception->getMessage());
    $fail('exchange_error');
}

// Linking rules (google-signin.md, fixed policy).
$identity = find_identity($pdo, 'google', $sub);
if ($identity !== null) {
    $userId = (int) $identity['user_id'];
} else {
    $existing = find_user_by_email($pdo, $email);
    if ($existing !== null) {
        if (user_has_identity($pdo, (int) $existing['id'], 'google')) {
            $fail('conflicting_identity');   // same email, different Google identity — never merge
        }
        insert_identity($pdo, (int) $existing['id'], 'google', $sub, $email);
        log_activity($pdo, 'identity_linked', [
            'actor_type' => 'user', 'actor_id' => (int) $existing['id'],
            'entity' => 'users', 'entity_id' => (int) $existing['id'], 'details' => ['provider' => 'google'],
        ]);
        $userId = (int) $existing['id'];
    } else {
        try {
            $pdo->beginTransaction();
            $user = insert_user($pdo, $email, $name, null, true);
            insert_identity($pdo, (int) $user['id'], 'google', $sub, $email);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('google user create: ' . $exception->getMessage());
            $fail('create_error');
        }
        $userId = (int) $user['id'];
        log_activity($pdo, 'user_registered', [
            'actor_type' => 'user', 'actor_id' => $userId, 'actor_label' => $name,
            'entity' => 'users', 'entity_id' => $userId, 'details' => ['method' => 'google'],
        ]);
    }
}

record_attempt($pdo, $email, client_ip(), 'google', true);

// 2FA applies to Google sign-ins exactly as to passwords.
$user = find_user($pdo, $userId);
if ($user !== null && $user['totp_enabled_at'] !== null) {
    session_regenerate_id(true);
    $_SESSION['pending_2fa_user_id']   = $userId;
    $_SESSION['pending_2fa_started_at'] = time();
    header('Location: /login/2fa');
    exit;
}

establish_session($userId);
log_activity($pdo, 'login', ['screen' => 'login', 'details' => ['method' => 'google']]);
header('Location: /');
exit;
