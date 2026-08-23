<?php
declare(strict_types=1);

/**
 * The live game loop (S3). Server-authoritative state machine:
 *   lobby → question → reveal → leaderboard → (question … | podium) → ended
 * The ONLY writers are advance_game() (host) and insert_answer() + the lazy
 * question→reveal transition in check_question_complete() (run on every poll).
 * No cron, no timers: time-based transitions happen lazily under a row lock.
 */

require_once __DIR__ . '/queries.php';

/**
 * Pure scoring (unit-tested): Kahoot formula on server-clocked response time.
 *   1000 points under 500ms, else floor((1 − (t / (T·1000)) / 2) × 1000);
 *   wrong = 0 and breaks the streak; +100 when a correct answer makes streak ≥ 2.
 */
function score_answer(int $responseMs, int $seconds, bool $correct, int $prevStreak, bool $streakBonus): array
{
    if (!$correct) {
        return ['points' => 0, 'streak_after' => 0];
    }
    $streakAfter = $prevStreak + 1;
    $limitMs = $seconds * 1000;
    $responseMs = max(0, min($responseMs, $limitMs));
    $points = $responseMs < 500
        ? 1000
        : (int) floor((1 - ($responseMs / $limitMs) / 2) * 1000);
    if ($streakBonus && $streakAfter >= 2) {
        $points += 100;
    }
    return ['points' => $points, 'streak_after' => $streakAfter];
}

/** The current question row (position = games.current_position) with its options. */
function find_current_game_question(PDO $pdo, array $game): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT gq.id, gq.question_id, gq.position, gq.points_possible, gq.started_at, gq.deadline,
               q.stem, q.explanation, q.scenario_id,
               s.title AS scenario_title, s.body AS scenario_body,
               greatest(0, ceil(extract(epoch FROM (gq.deadline - now()))))::int AS seconds_remaining
        FROM game_questions gq
        JOIN questions q ON q.id = gq.question_id
        LEFT JOIN scenarios s ON s.id = q.scenario_id
        WHERE gq.game_id = :game_id AND gq.position = :position
    SQL);
    $stmt->execute(['game_id' => $game['id'], 'position' => $game['current_position']]);
    $gq = $stmt->fetch();
    if ($gq === false) {
        return null;
    }
    $optStmt = $pdo->prepare(<<<'SQL'
        SELECT id, option_text, is_correct, display_order
        FROM question_options
        WHERE question_id = :question_id
        ORDER BY display_order
    SQL);
    $optStmt->execute(['question_id' => $gq['question_id']]);
    $gq['options'] = $optStmt->fetchAll();
    return $gq;
}

function count_live_players(PDO $pdo, int $gameId): int
{
    $stmt = $pdo->prepare('SELECT count(*) FROM game_players WHERE game_id = :id AND kicked_at IS NULL');
    $stmt->execute(['id' => $gameId]);
    return (int) $stmt->fetchColumn();
}

function count_answers_for(PDO $pdo, int $gameQuestionId): int
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT count(*) FROM answers a
        JOIN game_players gp ON gp.id = a.game_player_id
        WHERE a.game_question_id = :gq_id AND gp.kicked_at IS NULL
    SQL);
    $stmt->execute(['gq_id' => $gameQuestionId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Lazy question→reveal transition, row-locked so concurrent polls fire it once:
 * reveal when the deadline passed OR every live player has answered.
 * Never call inside another transaction.
 */
function check_question_complete(PDO $pdo, int $gameId): bool
{
    $stmt = $pdo->prepare('SELECT state FROM games WHERE id = :id');
    $stmt->execute(['id' => $gameId]);
    if ($stmt->fetchColumn() !== 'question') {
        return false;
    }
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT * FROM games WHERE id = :id FOR UPDATE');
        $lock->execute(['id' => $gameId]);
        $game = $lock->fetch();
        if ($game === false || $game['state'] !== 'question') {
            $pdo->commit();
            return false;
        }
        if ($game['question_deadline'] === null) {   // scenario intro — clock not started
            $pdo->commit();
            return false;
        }
        $dueStmt = $pdo->prepare('SELECT now() >= question_deadline FROM games WHERE id = :id');
        $dueStmt->execute(['id' => $gameId]);
        $deadlinePassed = (bool) $dueStmt->fetchColumn();

        $gqStmt = $pdo->prepare('SELECT id FROM game_questions WHERE game_id = :id AND position = :position');
        $gqStmt->execute(['id' => $gameId, 'position' => $game['current_position']]);
        $gqId = (int) $gqStmt->fetchColumn();

        $live     = count_live_players($pdo, $gameId);
        $answered = count_answers_for($pdo, $gqId);

        if (!$deadlinePassed && ($live === 0 || $answered < $live)) {
            $pdo->commit();
            return false;
        }
        $pdo->prepare("UPDATE games SET state = 'reveal' WHERE id = :id")->execute(['id' => $gameId]);
        log_activity($pdo, 'question_revealed', [
            'actor_type' => 'system', 'game_id' => $gameId, 'entity' => 'games', 'entity_id' => $gameId,
            'details' => ['position' => (int) $game['current_position'],
                          'trigger' => $deadlinePassed ? 'deadline' : 'all_answered'],
        ]);
        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/**
 * Host-driven transition, guarded by expected_state (double-click safe).
 * Returns ['ok' => bool, 'state' => current state after the call].
 */
function advance_game(PDO $pdo, int $gameId, int $hostUserId, string $expectedState): array
{
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT * FROM games WHERE id = :id FOR UPDATE');
        $lock->execute(['id' => $gameId]);
        $game = $lock->fetch();
        if ($game === false || (int) $game['host_user_id'] !== $hostUserId) {
            $pdo->commit();
            return ['ok' => false, 'state' => $game === false ? 'missing' : (string) $game['state']];
        }
        // S6: proceeding past a scenario intro starts the current question's clock.
        if ($game['state'] === 'question' && $game['scenario_intro_for'] !== null) {
            if ($expectedState !== 'scenario_intro') {
                $pdo->commit();
                return ['ok' => false, 'state' => 'question'];
            }
            $stmt = $pdo->prepare(<<<'SQL'
                UPDATE games
                SET scenario_intro_for = NULL,
                    question_started_at = now(),
                    question_deadline = now() + make_interval(secs => :seconds)
                WHERE id = :id
            SQL);
            $stmt->execute(['seconds' => $game['seconds_per_question'], 'id' => $gameId]);
            $gqStmt = $pdo->prepare(<<<'SQL'
                UPDATE game_questions
                SET started_at = now(), deadline = now() + make_interval(secs => :seconds)
                WHERE game_id = :id AND position = :position
                RETURNING question_id
            SQL);
            $gqStmt->execute(['seconds' => $game['seconds_per_question'], 'id' => $gameId,
                              'position' => $game['current_position']]);
            $questionId = (int) $gqStmt->fetchColumn();
            log_activity($pdo, 'question_shown', [
                'game_id' => $gameId, 'entity' => 'questions', 'entity_id' => $questionId,
                'screen' => 'game-host', 'details' => ['position' => (int) $game['current_position']],
            ]);
            $pdo->commit();
            return ['ok' => true, 'state' => 'question'];
        }

        if ($game['state'] !== $expectedState) {
            $pdo->commit();
            return ['ok' => false, 'state' => (string) $game['state']];
        }

        $startQuestion = function (int $position) use ($pdo, $game, $gameId): void {
            // S6: a question opening a NEW scenario shows the intro first — the clock
            // stays stopped (NULL deadline) until the host proceeds past the reading.
            $scStmt = $pdo->prepare(<<<'SQL'
                SELECT q.scenario_id,
                       (SELECT q2.scenario_id
                        FROM game_questions gq2 JOIN questions q2 ON q2.id = gq2.question_id
                        WHERE gq2.game_id = gq.game_id AND gq2.position = gq.position - 1) AS prev_scenario_id
                FROM game_questions gq
                JOIN questions q ON q.id = gq.question_id
                WHERE gq.game_id = :id AND gq.position = :position
            SQL);
            $scStmt->execute(['id' => $gameId, 'position' => $position]);
            $scenarioRow = $scStmt->fetch();
            $scenarioId = $scenarioRow !== false && $scenarioRow['scenario_id'] !== null
                ? (int) $scenarioRow['scenario_id'] : null;
            $prevScenarioId = $scenarioRow !== false && $scenarioRow['prev_scenario_id'] !== null
                ? (int) $scenarioRow['prev_scenario_id'] : null;
            if ($scenarioId !== null && $scenarioId !== $prevScenarioId) {
                $stmt = $pdo->prepare(<<<'SQL'
                    UPDATE games
                    SET state = 'question', current_position = :position,
                        started_at = COALESCE(started_at, now()),
                        question_started_at = NULL, question_deadline = NULL,
                        scenario_intro_for = :scenario_id
                    WHERE id = :id
                SQL);
                $stmt->execute(['position' => $position, 'scenario_id' => $scenarioId, 'id' => $gameId]);
                log_activity($pdo, 'scenario_shown', [
                    'game_id' => $gameId, 'entity' => 'scenarios', 'entity_id' => $scenarioId,
                    'screen' => 'game-host', 'details' => ['position' => $position],
                ]);
                return;
            }
            $stmt = $pdo->prepare(<<<'SQL'
                UPDATE games
                SET state = 'question', current_position = :position,
                    started_at = COALESCE(started_at, now()),
                    question_started_at = now(),
                    question_deadline = now() + make_interval(secs => :seconds),
                    scenario_intro_for = NULL
                WHERE id = :id
            SQL);
            $stmt->execute(['position' => $position, 'seconds' => $game['seconds_per_question'], 'id' => $gameId]);
            $gqStmt = $pdo->prepare(<<<'SQL'
                UPDATE game_questions
                SET started_at = now(), deadline = now() + make_interval(secs => :seconds)
                WHERE game_id = :id AND position = :position
                RETURNING question_id
            SQL);
            $gqStmt->execute(['seconds' => $game['seconds_per_question'], 'id' => $gameId, 'position' => $position]);
            $questionId = (int) $gqStmt->fetchColumn();
            log_activity($pdo, 'question_shown', [
                'game_id' => $gameId, 'entity' => 'questions', 'entity_id' => $questionId,
                'screen' => 'game-host', 'details' => ['position' => $position],
            ]);
        };

        switch ($game['state']) {
            case 'lobby':
                if (count_live_players($pdo, $gameId) === 0) {
                    $pdo->commit();
                    return ['ok' => false, 'state' => 'lobby'];
                }
                log_activity($pdo, 'game_started', [
                    'game_id' => $gameId, 'entity' => 'games', 'entity_id' => $gameId, 'screen' => 'game-host',
                ]);
                $startQuestion(1);
                break;
            case 'reveal':
                $pdo->prepare("UPDATE games SET state = 'leaderboard' WHERE id = :id")->execute(['id' => $gameId]);
                break;
            case 'leaderboard':
                if ((int) $game['current_position'] < (int) $game['question_count']) {
                    $startQuestion((int) $game['current_position'] + 1);
                } else {
                    finalize_game($pdo, $gameId);
                    $pdo->prepare("UPDATE games SET state = 'podium' WHERE id = :id")->execute(['id' => $gameId]);
                    log_activity($pdo, 'game_finished', [
                        'game_id' => $gameId, 'entity' => 'games', 'entity_id' => $gameId, 'screen' => 'game-host',
                        'details' => ['podium' => find_podium($pdo, $gameId)],
                    ]);
                }
                break;
            case 'podium':
                $pdo->prepare("UPDATE games SET state = 'ended', ended_at = now() WHERE id = :id")->execute(['id' => $gameId]);
                break;
            default:
                $pdo->commit();
                return ['ok' => false, 'state' => (string) $game['state']];
        }

        $stateStmt = $pdo->prepare('SELECT state FROM games WHERE id = :id');
        $stateStmt->execute(['id' => $gameId]);
        $newState = (string) $stateStmt->fetchColumn();
        $pdo->commit();
        return ['ok' => true, 'state' => $newState];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Writes final_score/final_rank for live players. Runs inside the caller's transaction. */
function finalize_game(PDO $pdo, int $gameId): void
{
    $pdo->prepare(<<<'SQL'
        UPDATE game_players gp
        SET final_score = t.total, final_rank = t.rnk
        FROM (
            SELECT gp2.id,
                   COALESCE(sum(a.points_awarded), 0) AS total,
                   RANK() OVER (ORDER BY COALESCE(sum(a.points_awarded), 0) DESC) AS rnk
            FROM game_players gp2
            LEFT JOIN answers a ON a.game_player_id = gp2.id
            WHERE gp2.game_id = :game_id AND gp2.kicked_at IS NULL
            GROUP BY gp2.id
        ) t
        WHERE gp.id = t.id
    SQL)->execute(['game_id' => $gameId]);
}

/**
 * One player's answer for the current question. Server-clocked, idempotent
 * (duplicate answers return the stored row), streak derived from position−1
 * (a missed question yields no row, so the derivation treats it as a break).
 * Returns ['status' => 'ok'|'duplicate'|'late'|'invalid', 'answer' => ?array].
 */
function insert_answer(PDO $pdo, array $player, int $optionId): array
{
    $game = find_game($pdo, (int) $player['game_id']);
    if ($game === null || $game['state'] !== 'question') {
        return ['status' => 'late', 'answer' => null];
    }
    $gq = find_current_game_question($pdo, $game);
    if ($gq === null) {
        return ['status' => 'late', 'answer' => null];
    }

    $timing = $pdo->prepare(<<<'SQL'
        SELECT now() <= deadline AS in_time,
               greatest(0, floor(extract(epoch FROM (now() - started_at)) * 1000))::int AS response_ms
        FROM game_questions WHERE id = :id
    SQL);
    $timing->execute(['id' => $gq['id']]);
    $clock = $timing->fetch();
    if (!$clock || !$clock['in_time']) {
        return ['status' => 'late', 'answer' => null];
    }

    $option = null;
    foreach ($gq['options'] as $candidate) {
        if ((int) $candidate['id'] === $optionId) {
            $option = $candidate;
            break;
        }
    }
    if ($option === null) {
        return ['status' => 'invalid', 'answer' => null];
    }

    $prevStreak = 0;
    if ((int) $gq['position'] > 1) {
        $prevStmt = $pdo->prepare(<<<'SQL'
            SELECT a.is_correct, a.streak_after
            FROM answers a
            JOIN game_questions pgq ON pgq.id = a.game_question_id
            WHERE a.game_player_id = :player_id AND pgq.game_id = :game_id AND pgq.position = :prev_position
        SQL);
        $prevStmt->execute([
            'player_id' => $player['id'], 'game_id' => $game['id'],
            'prev_position' => (int) $gq['position'] - 1,
        ]);
        $prev = $prevStmt->fetch();
        if ($prev !== false && $prev['is_correct']) {
            $prevStreak = (int) $prev['streak_after'];
        }
    }

    $score = score_answer(
        (int) $clock['response_ms'],
        (int) $game['seconds_per_question'],
        (bool) $option['is_correct'],
        $prevStreak,
        (bool) $game['streak_bonus']
    );

    try {
        $stmt = $pdo->prepare(<<<'SQL'
            INSERT INTO answers (game_player_id, game_question_id, option_id, is_correct, response_ms, points_awarded, streak_after)
            VALUES (:player_id, :gq_id, :option_id, :is_correct, :response_ms, :points, :streak_after)
            RETURNING *
        SQL);
        $stmt->execute([
            'player_id' => $player['id'], 'gq_id' => $gq['id'], 'option_id' => $optionId,
            'is_correct' => $option['is_correct'] ? 't' : 'f',
            'response_ms' => $clock['response_ms'], 'points' => $score['points'],
            'streak_after' => $score['streak_after'],
        ]);
        $answer = $stmt->fetch();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23505') {
            $dupStmt = $pdo->prepare('SELECT * FROM answers WHERE game_player_id = :player_id AND game_question_id = :gq_id');
            $dupStmt->execute(['player_id' => $player['id'], 'gq_id' => $gq['id']]);
            return ['status' => 'duplicate', 'answer' => $dupStmt->fetch() ?: null];
        }
        throw $exception;
    }

    log_activity($pdo, 'answer_submitted', [
        'actor_type' => 'player', 'actor_id' => (int) $player['id'], 'actor_label' => $player['nickname'],
        'game_id' => (int) $game['id'], 'entity' => 'answers', 'entity_id' => (int) $answer['id'],
        'details' => ['position' => (int) $gq['position'], 'correct' => (bool) $option['is_correct'],
                      'response_ms' => (int) $clock['response_ms'], 'points' => $score['points']],
    ]);
    check_question_complete($pdo, (int) $game['id']);
    return ['status' => 'ok', 'answer' => $answer];
}

/** Answer distribution for a game question: per option, count of picks. */
function find_answer_distribution(PDO $pdo, int $gameQuestionId, array $options): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT option_id, count(*) AS picks
        FROM answers WHERE game_question_id = :gq_id GROUP BY option_id
    SQL);
    $stmt->execute(['gq_id' => $gameQuestionId]);
    $byOption = array_column($stmt->fetchAll(), 'picks', 'option_id');
    $total = array_sum($byOption);
    $rows = [];
    foreach ($options as $option) {
        $picks = (int) ($byOption[$option['id']] ?? 0);
        $rows[] = $option + [
            'picks' => $picks,
            'pct'   => $total > 0 ? (int) round(100 * $picks / $total) : 0,
        ];
    }
    return $rows;
}

/**
 * Live totals per player. Includes rank now and rank as of the previous position
 * (for leaderboard deltas). Live players only, ordered by current rank.
 */
function find_scoreboard(PDO $pdo, int $gameId, int $currentPosition): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT gp.id, gp.nickname, gp.user_id,
               COALESCE(sum(a.points_awarded), 0) AS total,
               COALESCE(sum(a.points_awarded) FILTER (WHERE gq.position < :position), 0) AS prev_total,
               COALESCE(max(a.streak_after) FILTER (WHERE gq.position = :position2), 0) AS current_streak
        FROM game_players gp
        LEFT JOIN answers a ON a.game_player_id = gp.id
        LEFT JOIN game_questions gq ON gq.id = a.game_question_id
        WHERE gp.game_id = :game_id AND gp.kicked_at IS NULL
        GROUP BY gp.id
        ORDER BY total DESC, gp.joined_at ASC
    SQL);
    $stmt->execute(['game_id' => $gameId, 'position' => $currentPosition, 'position2' => $currentPosition]);
    $rows = $stmt->fetchAll();

    $prevOrder = $rows;
    usort($prevOrder, static fn (array $a, array $b): int => $b['prev_total'] <=> $a['prev_total']);
    $prevRank = [];
    foreach ($prevOrder as $index => $row) {
        $prevRank[(int) $row['id']] = $index + 1;
    }
    foreach ($rows as $index => &$row) {
        $row['rank']      = $index + 1;
        $row['rank_delta'] = $prevRank[(int) $row['id']] - ($index + 1);   // + = moved up
    }
    return $rows;
}

/** Final podium (post-finalize): ranked live players. */
function find_podium(PDO $pdo, int $gameId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT id, nickname, user_id, final_score, final_rank
        FROM game_players
        WHERE game_id = :game_id AND kicked_at IS NULL AND final_rank IS NOT NULL
        ORDER BY final_rank ASC, joined_at ASC
    SQL);
    $stmt->execute(['game_id' => $gameId]);
    return $stmt->fetchAll();
}

/**
 * Resolve the host console's current stage after running the lazy transition.
 * Returns ['game' => row, 'view' => template, 'data' => vars for it].
 */
function find_host_stage(PDO $pdo, int $gameId): ?array
{
    check_question_complete($pdo, $gameId);
    $game = find_game($pdo, $gameId);
    if ($game === null) {
        return null;
    }
    $version = find_game_version($pdo, $gameId);
    $base = ['game' => $game, 'version' => $version];

    switch ($game['state']) {
        case 'lobby':
        case 'ended':
        case 'aborted':
            return $base + ['view' => 'games/partials/host-players.php',
                            'data' => $base + ['players' => find_game_players($pdo, $gameId)]];
        case 'question':
            $gq = find_current_game_question($pdo, $game);
            if ($game['scenario_intro_for'] !== null) {
                return $base + ['view' => 'games/partials/host-scenario.php',
                                'data' => $base + ['gq' => $gq]];
            }
            return $base + ['view' => 'games/partials/host-question.php',
                            'data' => $base + [
                                'gq'       => $gq,
                                'answered' => count_answers_for($pdo, (int) $gq['id']),
                                'live'     => count_live_players($pdo, $gameId),
                            ]];
        case 'reveal':
            $gq = find_current_game_question($pdo, $game);
            return $base + ['view' => 'games/partials/host-reveal.php',
                            'data' => $base + [
                                'gq'           => $gq,
                                'distribution' => find_answer_distribution($pdo, (int) $gq['id'], $gq['options']),
                                'live'         => count_live_players($pdo, $gameId),
                            ]];
        case 'leaderboard':
            return $base + ['view' => 'games/partials/host-leaderboard.php',
                            'data' => $base + [
                                'scoreboard' => find_scoreboard($pdo, $gameId, (int) $game['current_position']),
                                'isLast'     => (int) $game['current_position'] >= (int) $game['question_count'],
                            ]];
        case 'podium':
            return $base + ['view' => 'games/partials/host-podium.php',
                            'data' => $base + ['podium' => find_podium($pdo, $gameId)]];
    }
    return null;
}

/**
 * Resolve one player's current stage (their poll + post-answer view).
 * Kicked/terminal handling stays in the controllers (S2). ['view','data','terminal'].
 */
function find_player_stage(PDO $pdo, array $player, array $game): array
{
    check_question_complete($pdo, (int) $game['id']);
    $game = find_game($pdo, (int) $game['id']);   // reload post-transition
    $version = find_game_version($pdo, (int) $game['id']);
    $base = ['game' => $game, 'player' => $player, 'version' => $version];

    switch ($game['state']) {
        case 'question':
            $gq = find_current_game_question($pdo, $game);
            if ($game['scenario_intro_for'] !== null) {
                return ['view' => 'play/scenario.php', 'terminal' => false, 'data' => $base + ['gq' => $gq]];
            }
            $mine = find_player_answer($pdo, (int) $player['id'], (int) $gq['id']);
            if ($mine !== null) {
                return ['view' => 'play/locked-in.php', 'terminal' => false,
                        'data' => $base + ['gq' => $gq, 'answer' => $mine]];
            }
            return ['view' => 'play/question.php', 'terminal' => false, 'data' => $base + ['gq' => $gq]];
        case 'reveal':
            $gq = find_current_game_question($pdo, $game);
            return ['view' => 'play/reveal.php', 'terminal' => false,
                    'data' => $base + [
                        'gq'     => $gq,
                        'answer' => find_player_answer($pdo, (int) $player['id'], (int) $gq['id']),
                        'total'  => find_player_total($pdo, (int) $player['id']),
                    ]];
        case 'leaderboard':
            $scoreboard = find_scoreboard($pdo, (int) $game['id'], (int) $game['current_position']);
            $mine = null;
            foreach ($scoreboard as $row) {
                if ((int) $row['id'] === (int) $player['id']) {
                    $mine = $row;
                    break;
                }
            }
            return ['view' => 'play/leaderboard.php', 'terminal' => false, 'data' => $base + ['mine' => $mine]];
        case 'podium':
            $podium = find_podium($pdo, (int) $game['id']);
            $mine = null;
            foreach ($podium as $row) {
                if ((int) $row['id'] === (int) $player['id']) {
                    $mine = $row;
                    break;
                }
            }
            return ['view' => 'play/podium.php', 'terminal' => true, 'data' => $base + ['mine' => $mine]];
        default:
            $players   = find_game_players($pdo, (int) $game['id']);
            $liveCount = count(array_filter($players, static fn (array $p): bool => $p['kicked_at'] === null));
            return ['view' => 'play/lobby.php', 'terminal' => false,
                    'data' => $base + ['liveCount' => $liveCount]];
    }
}

function find_player_answer(PDO $pdo, int $playerId, int $gameQuestionId): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT a.*, o.option_text, o.display_order
        FROM answers a
        JOIN question_options o ON o.id = a.option_id
        WHERE a.game_player_id = :player_id AND a.game_question_id = :gq_id
    SQL);
    $stmt->execute(['player_id' => $playerId, 'gq_id' => $gameQuestionId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function find_player_total(PDO $pdo, int $playerId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(sum(points_awarded), 0) FROM answers WHERE game_player_id = :id');
    $stmt->execute(['id' => $playerId]);
    return (int) $stmt->fetchColumn();
}
