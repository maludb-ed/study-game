-- group_domain_performance(PDO, int examId, ?string since)
-- ALL answers count here (claimed + unclaimed) per the analytics build spec amendment 3.
-- One row per domain of the exam, even domains with zero answers. Also returns the raw
-- 30-day-vs-earlier windows so the caller can derive the trend arrow (amendment 4).
SELECT
    d.id                                                                                    AS domain_id,
    d.name                                                                                  AS domain_name,
    d.weight_pct,
    d.display_order,
    count(a.id)                                                                              AS answered_count,
    count(a.id) FILTER (WHERE a.is_correct)                                                  AS correct_count,
    count(a.id) FILTER (WHERE a.answered_at >= now() - interval '30 days')                   AS recent_answered,
    count(a.id) FILTER (WHERE a.answered_at >= now() - interval '30 days' AND a.is_correct)  AS recent_correct,
    count(a.id) FILTER (WHERE a.answered_at <  now() - interval '30 days')                   AS prior_answered,
    count(a.id) FILTER (WHERE a.answered_at <  now() - interval '30 days' AND a.is_correct)  AS prior_correct
FROM domains d
LEFT JOIN questions q       ON q.domain_id = d.id
LEFT JOIN game_questions gq ON gq.question_id = q.id
LEFT JOIN answers a         ON a.game_question_id = gq.id
                            AND (:since::timestamptz IS NULL OR a.answered_at >= :since::timestamptz)
WHERE d.exam_id = :exam_id
GROUP BY d.id
ORDER BY d.display_order
