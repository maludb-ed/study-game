<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

auth_page_headers();
if (config('GOOGLE_CLIENT_ID') === '') {
    flash_set('warning', 'Google sign-in is not configured.');
    header('Location: /login');
    exit;
}

$provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => config('GOOGLE_CLIENT_ID'),
    'clientSecret' => config('GOOGLE_CLIENT_SECRET'),
    'redirectUri'  => config('APP_URL') . '/auth/google/callback',
]);

// PKCE + state, both session-bound; nonce for the ID token.
$_SESSION['oauth_nonce'] = bin2hex(random_bytes(16));
$authUrl = $provider->getAuthorizationUrl([
    'scope' => ['openid', 'email', 'profile'],
    'nonce' => $_SESSION['oauth_nonce'],
]);
$_SESSION['oauth2state']        = $provider->getState();
$_SESSION['oauth2pkceVerifier'] = method_exists($provider, 'getPkceCode') ? $provider->getPkceCode() : null;

header('Location: ' . $authUrl);
exit;
