-- most_missed_questions(PDO, int examId=0, int domainId=0, int page=1)
-- ALL answers count here (group analytics, amendment 3). Min 3 plays to appear
-- (HAVING clause), sorted miss rate desc, page size 25 (bound by the caller).
-- count(*) OVER() runs after GROUP BY/HAVING but before LIMIT/OFFSET, giving the
-- caller a correct pagination total in the same round trip.
SELECT
    q.id                                                                              AS question_id,
    q.stem,
    d.id                                                                              AS domain_id,
    d.name                                                                            AS domain_name,
    e.id                                                                              AS exam_id,
    e.code                                                                            AS exam_code,
    count(a.id)                                                                       AS plays,
    count(a.id) FILTER (WHERE NOT a.is_correct)                                       AS misses,
    round(100.0 * count(a.id) FILTER (WHERE NOT a.is_correct) / count(a.id))::int     AS miss_rate_pct,
    avg(a.response_ms)                                                                AS avg_response_ms,
    count(*) OVER()                                                                   AS total_count
FROM questions q
JOIN domains d          ON d.id = q.domain_id
JOIN exams e             ON e.id = q.exam_id
JOIN game_questions gq   ON gq.question_id = q.id
JOIN answers a           ON a.game_question_id = gq.id
WHERE (:exam_id::bigint = 0 OR q.exam_id = :exam_id)
  AND (:domain_id::bigint = 0 OR q.domain_id = :domain_id)
GROUP BY q.id, d.id, e.id
HAVING count(a.id) >= 3
ORDER BY miss_rate_pct DESC, plays DESC
LIMIT :limit OFFSET :offset
