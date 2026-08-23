<?php
declare(strict_types=1);

/**
 * Analytics queries (S5). Single source of truth for the four named functions below —
 * their SQL lives in sql/{name}.sql (loaded here AND by the Phase 4 records-MCP server
 * from Python) so the screens and the assistant never disagree. Any additional small
 * SQL for headline tiles / rosters lives inline in this file (build spec amendment 1).
 *
 * Whose answers count (build spec amendment 3):
 *   - group_domain_performance, most_missed_questions -> ALL answers (claimed + not)
 *   - member_readiness, alltime_leaderboard           -> CLAIMED answers only
 *     (game_players.user_id set)
 */

const ANALYTICS_DRILL_PAGE_SIZE = 25;

/**
 * Per-domain performance for one exam, ALL answers. Returns one row per domain with
 * answered/correct counts, accuracy_pct (null if unanswered), a derived trend ('up'/
 * 'down'/'flat'/null — amendment 4), and weakest_member (claimed-answer only, amendment 3).
 */
function group_domain_performance(PDO $pdo, int $examId, ?string $since): array
{
    $stmt = $pdo->prepare((string) file_get_contents(__DIR__ . '/sql/group_domain_performance.sql'));
    $stmt->bindValue('exam_id', $examId, PDO::PARAM_INT);
    $stmt->bindValue('since', $since, $since === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $answered = (int) $row['answered_count'];
        $correct  = (int) $row['correct_count'];
        $row['accuracy_pct'] = $answered > 0 ? (int) round(100 * $correct / $answered) : null;

        $recentAnswered = (int) $row['recent_answered'];
        $recentCorrect  = (int) $row['recent_correct'];
        $priorAnswered  = (int) $row['prior_answered'];
        $priorCorrect   = (int) $row['prior_correct'];

        if ($recentAnswered === 0 || $priorAnswered === 0) {
            // Omit the arrow entirely when either window has no answers (amendment 4).
            $row['trend'] = null;
        } else {
            $delta = (100 * $recentCorrect / $recentAnswered) - (100 * $priorCorrect / $priorAnswered);
            $row['trend'] = $delta >= 5 ? 'up' : ($delta <= -5 ? 'down' : 'flat');
        }
    }
    unset($row);

    // Weakest claimed member per domain (lowest accuracy among members with >=1 claimed
    // answer in that domain). Claimed-answer scoped per amendment 3's footnote rule.
    $weakStmt = $pdo->prepare(<<<'SQL'
        SELECT d.id AS domain_id, u.display_name,
               count(a.id) AS answered, count(a.id) FILTER (WHERE a.is_correct) AS correct
        FROM domains d
        JOIN questions q       ON q.domain_id = d.id
        JOIN game_questions gq ON gq.question_id = q.id
        JOIN answers a         ON a.game_question_id = gq.id
        JOIN game_players gp   ON gp.id = a.game_player_id AND gp.user_id IS NOT NULL
        JOIN users u            ON u.id = gp.user_id
        WHERE d.exam_id = :exam_id
        GROUP BY d.id, u.id, u.display_name
    SQL);
    $weakStmt->execute(['exam_id' => $examId]);

    $weakest = [];
    foreach ($weakStmt->fetchAll() as $memberRow) {
        $answered = (int) $memberRow['answered'];
        if ($answered < 1) {
            continue;
        }
        $domainId = (int) $memberRow['domain_id'];
        $accuracy = 100 * ((int) $memberRow['correct']) / $answered;
        if (!isset($weakest[$domainId]) || $accuracy < $weakest[$domainId]['accuracy']) {
            $weakest[$domainId] = ['name' => $memberRow['display_name'], 'accuracy' => $accuracy];
        }
    }

    foreach ($rows as &$row) {
        $row['weakest_member'] = $weakest[(int) $row['domain_id']]['name'] ?? null;
    }
    unset($row);

    return $rows;
}

/**
 * One member's readiness for one exam. Locked formula (build spec amendment 2):
 *   domain_accuracy   = correct/answered per domain, claimed answers only
 *   weighted_accuracy = Σ(weight_pct × domain_accuracy) / 100, unseen domains contribute 0
 *   score             = round(100 + 900 × weighted_accuracy)
 *   coverage_pct      = Σ(weight_pct of domains with >=1 answered)
 * Bands: score>=720 AND coverage>=60 -> success "On track"; coverage<60 -> secondary
 * "Not enough data yet"; else 600-719 warning / <600 danger.
 */
function member_readiness(PDO $pdo, int $userId, int $examId): array
{
    $stmt = $pdo->prepare((string) file_get_contents(__DIR__ . '/sql/member_readiness.sql'));
    $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue('exam_id', $examId, PDO::PARAM_INT);
    $stmt->execute();
    $domains = $stmt->fetchAll();

    $weightedSum = 0.0;
    $coveragePct = 0.0;
    foreach ($domains as &$row) {
        $answered = (int) $row['answered_count'];
        $correct  = (int) $row['correct_count'];
        $accuracy = $answered > 0 ? $correct / $answered : null; // fraction 0..1, null = unseen
        $weight   = (float) $row['weight_pct'];

        $row['accuracy_pct']           = $accuracy === null ? null : (int) round(100 * $accuracy);
        $row['weighted_contribution']  = round($weight * ($accuracy ?? 0.0), 1);

        $weightedSum += $weight * ($accuracy ?? 0.0);
        if ($answered > 0) {
            $coveragePct += $weight;
        }
    }
    unset($row);

    $weightedAccuracy = $weightedSum / 100;
    $score        = (int) round(100 + 900 * $weightedAccuracy);
    $coveragePct  = round($coveragePct, 1);

    if ($coveragePct < 60) {
        $bandStatus = 'secondary';
        $bandLabel  = 'Not enough data yet';
    } elseif ($score >= 720) {
        $bandStatus = 'success';
        $bandLabel  = 'On track';
    } elseif ($score >= 600) {
        $bandStatus = 'warning';
        $bandLabel  = 'Almost ready';
    } else {
        $bandStatus = 'danger';
        $bandLabel  = 'Needs focus';
    }

    return [
        'user_id'      => $userId,
        'exam_id'      => $examId,
        'score'        => $score,
        'coverage_pct' => $coveragePct,
        'band_status'  => $bandStatus,
        'band_label'   => $bandLabel,
        'domains'      => $domains,
    ];
}

/**
 * Most-missed questions (drill list): ALL answers, minimum 3 plays, sorted miss rate
 * desc, paginated. Returns ['rows' => [...], 'total' => int] per the list-screen
 * convention (find_questions()).
 */
function most_missed_questions(PDO $pdo, int $examId = 0, int $domainId = 0, int $page = 1): array
{
    $page   = max(1, $page);
    $offset = ($page - 1) * ANALYTICS_DRILL_PAGE_SIZE;

    $stmt = $pdo->prepare((string) file_get_contents(__DIR__ . '/sql/most_missed_questions.sql'));
    $stmt->bindValue('exam_id', $examId, PDO::PARAM_INT);
    $stmt->bindValue('domain_id', $domainId, PDO::PARAM_INT);
    $stmt->bindValue('limit', ANALYTICS_DRILL_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $total = $rows === [] ? 0 : (int) $rows[0]['total_count'];
    foreach ($rows as &$row) {
        $row['avg_response_s'] = $row['avg_response_ms'] !== null
            ? round(((float) $row['avg_response_ms']) / 1000, 1)
            : null;
    }
    unset($row);

    return ['rows' => $rows, 'total' => $total];
}

/**
 * All-time leaderboard: claimed identities with >=1 submitted answer, ordered by total
 * points. $examId = 0 means all exams.
 */
function alltime_leaderboard(PDO $pdo, int $examId = 0): array
{
    $stmt = $pdo->prepare((string) file_get_contents(__DIR__ . '/sql/alltime_leaderboard.sql'));
    $stmt->bindValue('exam_id', $examId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $answered = (int) $row['answered_count'];
        $correct  = (int) $row['correct_count'];
        $row['accuracy_pct'] = $answered > 0 ? (int) round(100 * $correct / $answered) : null;
        $row['avg_rank']     = $row['avg_rank'] !== null ? round((float) $row['avg_rank'], 1) : null;
    }
    unset($row);

    return $rows;
}

// --- Supporting queries (headline tiles, rosters — amendment 1 allows these inline) ---

/** Lowest-id exam with at least one answer anywhere, else exam id 1 (amendment 5 default). */
function find_default_analytics_exam_id(PDO $pdo): int
{
    $examId = $pdo->query(<<<'SQL'
        SELECT min(g.exam_id)
        FROM answers a
        JOIN game_questions gq ON gq.id = a.game_question_id
        JOIN games g           ON g.id = gq.game_id
    SQL)->fetchColumn();

    return ($examId !== false && $examId !== null) ? (int) $examId : 1;
}

/**
 * Headline tiles for the group screen: games played (ended games), questions answered
 * (all answers), group weighted score (same locked formula, null when no answers at
 * all), members with a score >= 720. Reuses $domainPerformance already fetched by the
 * caller (same numbers as the domain table — no second source of truth) and
 * member_readiness() per user for the ready count.
 */
function find_group_tiles(PDO $pdo, int $examId, array $domainPerformance): array
{
    $gamesStmt = $pdo->prepare("SELECT count(*) FROM games WHERE exam_id = :exam_id AND state = 'ended'");
    $gamesStmt->execute(['exam_id' => $examId]);
    $gamesPlayed = (int) $gamesStmt->fetchColumn();

    $answeredTotal = 0;
    $weightedSum   = 0.0;
    foreach ($domainPerformance as $row) {
        $answeredTotal += (int) $row['answered_count'];
        $weight    = (float) $row['weight_pct'];
        $accuracy  = $row['accuracy_pct'] !== null ? $row['accuracy_pct'] / 100 : 0.0;
        $weightedSum += $weight * $accuracy;
    }
    $weightedScore = $answeredTotal > 0 ? (int) round(100 + 900 * ($weightedSum / 100)) : null;

    $readyCount = 0;
    foreach ($pdo->query('SELECT id FROM users ORDER BY id')->fetchAll() as $userRow) {
        $readiness = member_readiness($pdo, (int) $userRow['id'], $examId);
        if ($readiness['band_status'] === 'success') {
            $readyCount++;
        }
    }

    return [
        'games_played'       => $gamesPlayed,
        'questions_answered' => $answeredTotal,
        'weighted_score'     => $weightedScore,
        'members_ready'      => $readyCount,
    ];
}

/** Roster of members who've claimed a nickname in at least one game of this exam. */
function find_group_members(PDO $pdo, int $examId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT u.id AS user_id, u.display_name,
               count(DISTINCT gp.id) AS games_count,
               max(g.created_at)     AS last_played
        FROM game_players gp
        JOIN users u ON u.id = gp.user_id
        JOIN games g ON g.id = gp.game_id
        WHERE g.exam_id = :exam_id
        GROUP BY u.id
        ORDER BY u.display_name
    SQL);
    $stmt->execute(['exam_id' => $examId]);
    return $stmt->fetchAll();
}

function find_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, display_name, email FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Distinct exams this member has claimed a nickname in (any game state). */
function find_member_exams_played(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT DISTINCT g.exam_id, e.code
        FROM game_players gp
        JOIN games g ON g.id = gp.game_id
        JOIN exams e ON e.id = g.exam_id
        WHERE gp.user_id = :user_id
        ORDER BY g.exam_id
    SQL);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

/** Total claimed answers by this member, across every exam. */
function count_member_claimed_answers(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT count(*)
        FROM answers a
        JOIN game_players gp ON gp.id = a.game_player_id
        WHERE gp.user_id = :user_id
    SQL);
    $stmt->execute(['user_id' => $userId]);
    return (int) $stmt->fetchColumn();
}

/** Global count of unclaimed game_players rows — feeds the "claim on next join" note. */
function count_unclaimed_game_players(PDO $pdo): int
{
    return (int) $pdo->query('SELECT count(*) FROM game_players WHERE user_id IS NULL')->fetchColumn();
}
