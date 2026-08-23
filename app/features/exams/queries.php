<?php
declare(strict_types=1);

function find_exams_with_counts(PDO $pdo): array
{
    return $pdo->query(<<<'SQL'
        SELECT e.id, e.code, e.name, e.audience, e.official_question_count,
               e.duration_minutes, e.passing_score, e.price_usd, e.is_active,
               count(q.id) FILTER (WHERE q.status = 'active') AS active_questions,
               count(q.id)                                    AS total_questions
        FROM exams e
        LEFT JOIN questions q ON q.exam_id = e.id
        GROUP BY e.id
        ORDER BY e.code
    SQL)->fetchAll();
}

function find_exam(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $exam = $stmt->fetch();
    return $exam === false ? null : $exam;
}

/** Per-domain bank coverage vs the blueprint, for a game of $gameSize questions. */
function find_exam_coverage(PDO $pdo, int $examId, int $gameSize = 10): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT d.id, d.name, d.weight_pct, d.display_order,
               count(q.id) FILTER (WHERE q.status = 'active') AS active_questions,
               count(q.id) FILTER (WHERE q.status = 'draft')  AS draft_questions
        FROM domains d
        LEFT JOIN questions q ON q.domain_id = d.id
        WHERE d.exam_id = :exam_id
        GROUP BY d.id
        ORDER BY d.display_order
    SQL);
    $stmt->execute(['exam_id' => $examId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['target'] = (int) round(((float) $row['weight_pct'] / 100) * $gameSize);
        $row['short']  = ((int) $row['active_questions']) < $row['target'];
    }
    return $rows;
}
