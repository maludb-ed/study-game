<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/features/games/engine.php';
require_once dirname(__DIR__, 2) . '/app/features/exams/queries.php';

$user = require_login();
require_post();
verify_csrf();
$pdo = db();

$examId        = request_integer('exam_id') ?? 0;
$questionCount = request_integer('question_count') ?? 10;
$seconds       = request_integer('seconds_per_question') ?? 20;
$streakBonus   = ($_POST['streak_bonus'] ?? '') === '1';

$errors = [];
$exam   = $examId > 0 ? find_exam($pdo, $examId) : null;

if ($exam === null || !$exam['is_active']) {
    $errors[] = 'Choose an exam.';
}
if ($questionCount < 5 || $questionCount > 30) {
    $errors[] = 'Number of questions must be between 5 and 30.';
}
if (!in_array($seconds, [10, 20, 30, 60], true)) {
    $errors[] = 'Bad seconds-per-question value.';
}

if ($errors === [] && $exam !== null) {
    $coverage    = find_exam_coverage($pdo, $examId, $questionCount);
    $totalActive = array_sum(array_column($coverage, 'active_questions'));
    if ($totalActive < $questionCount) {
        $errors[] = sprintf(
            'This exam only has %d active questions; you need at least %d.',
            $totalActive,
            $questionCount
        );
        foreach ($coverage as $domain) {
            if ($domain['short']) {
                $errors[] = sprintf(
                    '%s: %d active (target %d).',
                    $domain['name'],
                    $domain['active_questions'],
                    $domain['target']
                );
            }
        }
    }
}

if ($errors !== []) {
    $exams = array_values(array_filter(
        find_exams_with_counts($pdo),
        static fn (array $e): bool => (bool) $e['is_active']
    ));
    if (acting_via_action_token()) {
        action_json(['status' => 'error', 'errors' => $errors]);
    }
    $content = view('games/partials/form.php', [
        'exams' => $exams, 'examId' => $examId, 'questionCount' => $questionCount,
        'seconds' => $seconds, 'streakBonus' => $streakBonus, 'errors' => $errors,
    ]);
    if (is_htmx_request()) {
        header('Vary: HX-Request');
        echo $content;
        exit;
    }
    echo view('layout.php', ['title' => 'New Game', 'content' => $content, 'active' => 'nav-game-new', 'user' => $user]);
    exit;
}

try {
    $game = insert_game($pdo, $examId, (int) $user['id'], $questionCount, $seconds, $streakBonus);
} catch (DomainException $exception) {
    $exams = array_values(array_filter(
        find_exams_with_counts($pdo),
        static fn (array $e): bool => (bool) $e['is_active']
    ));
    $content = view('games/partials/form.php', [
        'exams' => $exams, 'examId' => $examId, 'questionCount' => $questionCount,
        'seconds' => $seconds, 'streakBonus' => $streakBonus,
        'errors' => ['Not enough active questions became available for this exam. Try again.'],
    ]);
    if (is_htmx_request()) {
        header('Vary: HX-Request');
        echo $content;
        exit;
    }
    echo view('layout.php', ['title' => 'New Game', 'content' => $content, 'active' => 'nav-game-new', 'user' => $user]);
    exit;
}

$gameId = (int) $game['id'];
log_activity($pdo, 'game_created', [
    'screen' => 'game-new', 'entity' => 'games', 'entity_id' => $gameId, 'game_id' => $gameId,
    'details' => $game['draw'],
]);

$url = '/games/' . $gameId . '/host';
if (acting_via_action_token()) {
    action_json(['status' => 'success', 'action' => 'game_created', 'game_id' => $gameId,
                 'pin' => $game['pin'], 'url' => $url,
                 'note' => 'Players join at /join with the PIN; open the host console at the url.']);
}
if (is_htmx_request()) {
    header('Vary: HX-Request');
    header('HX-Push-Url: ' . $url);
    $stage = find_host_stage($pdo, $gameId);
    echo view('games/partials/host-lobby.php', [
        'game'      => $stage['game'],
        'stageHtml' => view($stage['view'], $stage['data']),
    ]);
    exit;
}
header('Location: ' . $url);
exit;
