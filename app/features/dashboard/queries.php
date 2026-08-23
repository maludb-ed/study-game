<?php
declare(strict_types=1);

function dashboard_stats(PDO $pdo): array
{
    return $pdo->query(<<<'SQL'
        SELECT
          (SELECT count(*) FROM users)                                   AS members,
          (SELECT count(*) FROM questions WHERE status = 'active')       AS active_questions,
          (SELECT count(*) FROM games WHERE state = 'ended')             AS games_played,
          (SELECT count(*) FROM answers)                                 AS answers_recorded
    SQL)->fetch();
}

function recent_activity(PDO $pdo, int $limit = 10): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT occurred_at, actor_label, action, screen, entity, entity_id
        FROM activity_log
        WHERE action <> 'screen_view'
        ORDER BY occurred_at DESC
        LIMIT :limit
    SQL);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
