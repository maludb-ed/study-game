-- member_readiness(PDO, int userId, int examId)
-- CLAIMED answers only (game_players.user_id = :user_id) per amendment 3. One row per
-- domain of the exam. active_question_count/seen_question_count scope to status='active'
-- questions only, matching the member grid's "seen/total active" column.
SELECT
    d.id                                                                             AS domain_id,
    d.name                                                                           AS domain_name,
    d.weight_pct,
    d.display_order,
    count(DISTINCT q.id) FILTER (WHERE q.status = 'active')                          AS active_question_count,
    count(DISTINCT CASE WHEN q.status = 'active' AND a.id IS NOT NULL THEN q.id END) AS seen_question_count,
    count(a.id)                                                                      AS answered_count,
    count(a.id) FILTER (WHERE a.is_correct)                                          AS correct_count
FROM domains d
LEFT JOIN questions q       ON q.domain_id = d.id
LEFT JOIN game_questions gq ON gq.question_id = q.id
LEFT JOIN answers a         ON a.game_question_id = gq.id
                            AND EXISTS (
                                SELECT 1 FROM game_players gp
                                WHERE gp.id = a.game_player_id AND gp.user_id = :user_id
                            )
WHERE d.exam_id = :exam_id
GROUP BY d.id
ORDER BY d.display_order
