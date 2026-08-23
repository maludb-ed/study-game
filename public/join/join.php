<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/queries.php';

require_post();
// Public, cookie-less until the player is seated — exempt from verify_csrf (amendment 9).
// Still rate-limited (pin_join: 10/min/IP) and validated.
$pdo = db();

$nicknameDenylist = ['fuck', 'shit', 'bitch', 'cunt', 'penis', 'nazi'];

// --- Step 2: nickname submission ----------------------------------------
if (isset($_POST['nickname'])) {
    $gameId   = request_integer('game_id') ?? 0;
    $nickname = trim((string) ($_POST['nickname'] ?? ''));
    $claimed  = ($_POST['claim'] ?? '') === '1';
    $user     = current_user();

    $game   = $gameId > 0 ? find_game($pdo, $gameId) : null;
    $errors = [];

    if ($game === null || $game['state'] !== 'lobby') {
        $errors[] = 'This game is no longer accepting players.';
    } else {
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 20) {
            $errors[] = 'Nickname must be 2–20 characters.';
        }
        if ($nickname !== '') {
            $loweredNickname = mb_strtolower($nickname);
            foreach ($nicknameDenylist as $bad) {
                if (str_contains($loweredNickname, $bad)) {
                    $errors[] = 'Pick a different nickname.';
                    break;
                }
            }
        }
        if ($errors === [] && find_game_player_by_nickname($pdo, $gameId, $nickname) !== null) {
            $errors[] = 'That nickname is already taken in this game.';
        }
    }

    if ($errors !== []) {
        $content = view('play/join-nickname.php', [
            'errors' => $errors, 'gameId' => $gameId, 'nickname' => $nickname,
            'claimed' => $claimed, 'user' => $user,
        ]);
        echo view('play/layout.php', ['title' => 'Join a Game', 'content' => $content]);
        exit;
    }

    $userId = ($claimed && $user !== null) ? (int) $user['id'] : null;
    $token  = bin2hex(random_bytes(32));
    $player = insert_game_player($pdo, $gameId, $nickname, $userId, $token);

    setcookie('player_token', $token, [
        'expires'  => time() + 12 * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);

    log_activity($pdo, 'player_joined', [
        'actor_type' => 'player', 'actor_id' => (int) $player['id'], 'actor_label' => $nickname,
        'screen' => 'join', 'entity' => 'game_players', 'entity_id' => (int) $player['id'], 'game_id' => $gameId,
    ]);
    if ($userId !== null) {
        log_activity($pdo, 'identity_claimed', [
            'actor_type' => 'user', 'actor_id' => $userId, 'actor_label' => $user['display_name'],
            'screen' => 'join', 'entity' => 'game_players', 'entity_id' => (int) $player['id'], 'game_id' => $gameId,
        ]);
    }

    redirect('/play');
}

// --- Step 1: PIN submission ---------------------------------------------
$pin = (string) preg_replace('/\D/', '', (string) ($_POST['pin'] ?? ''));
$ip  = client_ip();

if (too_many_attempts($pdo, null, $ip, 'pin_join')) {
    http_response_code(429);
    $content = view('play/join-pin.php', [
        'errors' => ['Too many attempts. Try again in a minute.'], 'old' => ['pin' => $pin],
    ]);
    echo view('play/layout.php', ['title' => 'Join a Game', 'content' => $content]);
    exit;
}

$game = strlen($pin) === 6 ? find_game_by_pin($pdo, $pin) : null;
record_attempt($pdo, null, $ip, 'pin_join', $game !== null);

if ($game === null) {
    $content = view('play/join-pin.php', ['errors' => ['That PIN was not found.'], 'old' => ['pin' => $pin]]);
    echo view('play/layout.php', ['title' => 'Join a Game', 'content' => $content]);
    exit;
}

// Rejoin: a cookie already seated in this game skips straight back to /play.
$existingToken = (string) ($_COOKIE['player_token'] ?? '');
if ($existingToken !== '') {
    $existingPlayer = find_game_player_by_token($pdo, $existingToken);
    if ($existingPlayer !== null
        && (int) $existingPlayer['game_id'] === (int) $game['id']
        && $existingPlayer['kicked_at'] === null
    ) {
        redirect('/play');
    }
}

if ($game['state'] !== 'lobby') {
    $content = view('play/join-pin.php', ['errors' => ['This game has already started.'], 'old' => ['pin' => $pin]]);
    echo view('play/layout.php', ['title' => 'Join a Game', 'content' => $content]);
    exit;
}

$content = view('play/join-nickname.php', [
    'errors' => [], 'gameId' => (int) $game['id'], 'nickname' => '',
    'claimed' => true, 'user' => current_user(),
]);
echo view('play/layout.php', ['title' => 'Join a Game', 'content' => $content]);
