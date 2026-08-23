<?php
declare(strict_types=1);

/**
 * Read-only queries for the game-report screen (S4). Self-contained: never writes,
 * never touches engine.php. Ranking source per the games-history build spec:
 *   state='ended'   -> final_rank/final_score, written once by finalize_game()
 *   otherwise       -> computed live, same RANK()-over-points formula finalize_game() uses
 * so an aborted/live game's report still shows a sensible standing.
 */

/** Game header (exam/host/player_count) + full ranking, or null if the game doesn't exist. */
function find_game_report(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT g.*, e.code AS exam_code, e.name AS exam_name, u.display_name AS host_name,
               (SELECT count(*) FROM game_players gp WHERE gp.game_id = g.id AND gp.kicked_at IS NULL) AS player_count
        FROM games g
        JOIN exams e ON e.id = g.exam_id
        JOIN users u ON u.id = g.host_user_id
        WHERE g.id = :id
    SQL);
    $stmt->execute(['id' => $id]);
    $game = $stmt->fetch();
    if ($game === false) {
        return null;
    }

    $game['ranking'] = $game['state'] === 'ended'
        ? find_game_ranking_final($pdo, $id)
        : find_game_ranking_computed($pdo, $id);

    return $game;
}

/** Ranking for state='ended': the final_rank/final_score finalize_game() already wrote. */
function find_game_ranking_final(PDO $pdo, int $gameId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT gp.id, gp.nickname, gp.user_id, u.display_name AS member_name,
               gp.final_rank AS rank, gp.final_score AS total,
               COALESCE(s.correct_count, 0)  AS correct_count,
               COALESCE(s.answered_count, 0) AS answered_count,
               COALESCE(s.best_streak, 0)    AS best_streak
        FROM game_players gp
        LEFT JOIN users u ON u.id = gp.user_id
        LEFT JOIN (
            SELECT game_player_id,
                   count(*) FILTER (WHERE is_correct) AS correct_count,
                   count(*)                            AS answered_count,
                   max(streak_after)                    AS best_streak
            FROM answers
            GROUP BY game_player_id
        ) s ON s.game_player_id = gp.id
        WHERE gp.game_id = :game_id AND gp.kicked_at IS NULL
        ORDER BY gp.final_rank ASC NULLS LAST, gp.joined_at ASC
    SQL);
    $stmt->execute(['game_id' => $gameId]);
    return $stmt->fetchAll();
}

/** Ranking for aborted/live games: computed on the fly, live (non-kicked) players only. */
function find_game_ranking_computed(PDO $pdo, int $gameId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT gp.id, gp.nickname, gp.user_id, u.display_name AS member_name,
               RANK() OVER (ORDER BY COALESCE(s.total, 0) DESC) AS rank,
               COALESCE(s.total, 0)          AS total,
               COALESCE(s.correct_count, 0)  AS correct_count,
               COALESCE(s.answered_count, 0) AS answered_count,
               COALESCE(s.best_streak, 0)    AS best_streak
        FROM game_players gp
        LEFT JOIN users u ON u.id = gp.user_id
        LEFT JOIN (
            SELECT game_player_id,
                   sum(points_awarded)                 AS total,
                   count(*) FILTER (WHERE is_correct)   AS correct_count,
                   count(*)                             AS answered_count,
                   max(streak_after)                    AS best_streak
            FROM answers
            GROUP BY game_player_id
        ) s ON s.game_player_id = gp.id
        WHERE gp.game_id = :game_id AND gp.kicked_at IS NULL
        ORDER BY rank ASC, gp.joined_at ASC
    SQL);
    $stmt->execute(['game_id' => $gameId]);
    return $stmt->fetchAll();
}

/**
 * Per-question breakdown, ordered by position. correct_pct is correct answers over the
 * LIVE PLAYER COUNT (not answers received) so unanswered players count against the rate —
 * the study-relevant number. avg_response_s (1 decimal) is over received answers only,
 * null when a question got none.
 */
function find_game_question_stats(PDO $pdo, int $id): array
{
    $liveStmt = $pdo->prepare('SELECT count(*) FROM game_players WHERE game_id = :id AND kicked_at IS NULL');
    $liveStmt->execute(['id' => $id]);
    $livePlayers = (int) $liveStmt->fetchColumn();

    $gqStmt = $pdo->prepare(<<<'SQL'
        SELECT gq.id, gq.position, gq.question_id, q.stem, d.name AS domain_name
        FROM game_questions gq
        JOIN questions q ON q.id = gq.question_id
        JOIN domains d ON d.id = q.domain_id
        WHERE gq.game_id = :id
        ORDER BY gq.position
    SQL);
    $gqStmt->execute(['id' => $id]);
    $questions = $gqStmt->fetchAll();

    $optStmt = $pdo->prepare(<<<'SQL'
        SELECT id, option_text, is_correct, display_order
        FROM question_options
        WHERE question_id = :question_id
        ORDER BY display_order
    SQL);

    $answerStmt = $pdo->prepare(<<<'SQL'
        SELECT a.option_id, a.is_correct, a.response_ms
        FROM answers a
        JOIN game_players gp ON gp.id = a.game_player_id
        WHERE a.game_question_id = :gq_id AND gp.kicked_at IS NULL
    SQL);

    $rows = [];
    foreach ($questions as $question) {
        $optStmt->execute(['question_id' => $question['question_id']]);
        $options = $optStmt->fetchAll();

        $answerStmt->execute(['gq_id' => $question['id']]);
        $answers = $answerStmt->fetchAll();

        $answeredCount = count($answers);
        $correctCount  = 0;
        $responseSum   = 0;
        $picksByOption = [];
        foreach ($answers as $answer) {
            if ($answer['is_correct']) {
                $correctCount++;
            }
            $responseSum += (int) $answer['response_ms'];
            $optionId = (int) $answer['option_id'];
            $picksByOption[$optionId] = ($picksByOption[$optionId] ?? 0) + 1;
        }

        $optionRows = [];
        foreach ($options as $option) {
            $picks = $picksByOption[(int) $option['id']] ?? 0;
            $optionRows[] = [
                'option_text'   => $option['option_text'],
                'display_order' => $option['display_order'],
                'is_correct'    => $option['is_correct'],
                'picks'         => $picks,
                'pct'           => $answeredCount > 0 ? (int) round(100 * $picks / $answeredCount) : 0,
            ];
        }

        $rows[] = [
            'position'       => $question['position'],
            'question_id'    => $question['question_id'],
            'stem'           => $question['stem'],
            'domain_name'    => $question['domain_name'],
            'correct_pct'    => $livePlayers > 0 ? (int) round(100 * $correctCount / $livePlayers) : 0,
            'avg_response_s' => $answeredCount > 0 ? round($responseSum / $answeredCount / 1000, 1) : null,
            'options'        => $optionRows,
        ];
    }

    return $rows;
}
