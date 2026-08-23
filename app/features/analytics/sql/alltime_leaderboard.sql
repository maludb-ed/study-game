-- alltime_leaderboard(PDO, int examId=0)
-- Claimed identities only (game_players.user_id set), AND requires at least one
-- submitted answer (inner join on answers) — a claimed nickname that never answered
-- anything is not a leaderboard entry. Ordered by total points desc.
SELECT
    u.id                                                       AS user_id,
    u.display_name,
    count(DISTINCT gp.id)                                      AS games_played,
    coalesce(sum(a.points_awarded), 0)                         AS total_points,
    count(a.id)                                                AS answered_count,
    count(a.id) FILTER (WHERE a.is_correct)                    AS correct_count,
    avg(gp.final_rank) FILTER (WHERE gp.final_rank IS NOT NULL) AS avg_rank
FROM game_players gp
JOIN users u   ON u.id = gp.user_id
JOIN games g   ON g.id = gp.game_id
JOIN answers a ON a.game_player_id = gp.id
WHERE (:exam_id::bigint = 0 OR g.exam_id = :exam_id)
GROUP BY u.id
ORDER BY total_points DESC, u.display_name ASC
