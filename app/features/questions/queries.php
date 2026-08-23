<?php
declare(strict_types=1);

const QUESTIONS_PAGE_SIZE = 25;

/** List with filters + pagination. Returns ['rows' => [...], 'total' => int]. */
function find_questions(
    PDO $pdo,
    int $examId = 0,
    int $domainId = 0,
    string $status = '',
    string $search = '',
    string $sort = 'updated_at',
    int $page = 1
): array {
    $allowedSort = [
        'updated_at' => 'q.updated_at DESC',
        'stem'       => 'q.stem ASC',
        'difficulty' => "array_position(ARRAY['easy','medium','hard'], q.difficulty), q.stem",
    ];
    $orderBy = $allowedSort[$sort] ?? $allowedSort['updated_at'];

    $where  = [];
    $params = [];
    if ($examId > 0)   { $where[] = 'q.exam_id = :exam_id';     $params['exam_id'] = $examId; }
    if ($domainId > 0) { $where[] = 'q.domain_id = :domain_id'; $params['domain_id'] = $domainId; }
    if ($status !== '' && in_array($status, ['draft', 'active', 'retired'], true)) {
        $where[] = 'q.status = :status';
        $params['status'] = $status;
    }
    if ($search !== '') {
        $where[] = '(q.stem ILIKE :search OR q.explanation ILIKE :search)';
        $params['search'] = '%' . $search . '%';
    }
    $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT count(*) FROM questions q {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $page   = max(1, $page);
    $offset = ($page - 1) * QUESTIONS_PAGE_SIZE;
    $stmt = $pdo->prepare(<<<SQL
        SELECT q.id, q.stem, q.difficulty, q.status, q.updated_at,
               e.code AS exam_code, d.name AS domain_name
        FROM questions q
        JOIN exams e ON e.id = q.exam_id
        JOIN domains d ON d.id = q.domain_id
        {$whereSql}
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset
    SQL);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', QUESTIONS_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/** One question with its options (ordered) and play stats. */
function find_question(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT q.*, e.code AS exam_code, e.name AS exam_name, d.name AS domain_name,
               u.display_name AS author_name,
               (SELECT count(*) FROM game_questions gq
                 JOIN answers a ON a.game_question_id = gq.id
                 WHERE gq.question_id = q.id)                            AS times_answered,
               (SELECT count(*) FROM game_questions gq
                 JOIN answers a ON a.game_question_id = gq.id
                 WHERE gq.question_id = q.id AND a.is_correct)           AS times_correct,
               (SELECT count(*) FROM game_questions gq WHERE gq.question_id = q.id) AS times_drawn
        FROM questions q
        JOIN exams e ON e.id = q.exam_id
        JOIN domains d ON d.id = q.domain_id
        LEFT JOIN users u ON u.id = q.created_by
        WHERE q.id = :id
    SQL);
    $stmt->execute(['id' => $id]);
    $question = $stmt->fetch();
    if ($question === false) {
        return null;
    }
    $optStmt = $pdo->prepare(<<<'SQL'
        SELECT id, option_text, is_correct, display_order
        FROM question_options
        WHERE question_id = :id
        ORDER BY display_order
    SQL);
    $optStmt->execute(['id' => $id]);
    $question['options'] = $optStmt->fetchAll();
    return $question;
}

/**
 * $options: list of ['text' => string, 'correct' => bool] in display order.
 * Runs in its own transaction (question + options + activity row belong together
 * — the caller passes $logActor for the log row).
 */
function insert_question(
    PDO $pdo,
    int $examId,
    int $domainId,
    string $stem,
    array $options,
    string $explanation,
    string $difficulty,
    string $status,
    string $source,
    ?int $createdBy
): array {
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO questions (exam_id, domain_id, stem, explanation, difficulty, status, source, created_by)
        VALUES (:exam_id, :domain_id, :stem, :explanation, :difficulty, :status, :source, :created_by)
        RETURNING id, exam_id, domain_id, stem, explanation, difficulty, status, source
    SQL);
    $stmt->execute([
        'exam_id' => $examId, 'domain_id' => $domainId, 'stem' => $stem,
        'explanation' => $explanation, 'difficulty' => $difficulty,
        'status' => $status, 'source' => $source, 'created_by' => $createdBy,
    ]);
    $question = $stmt->fetch();
    replace_question_options($pdo, (int) $question['id'], $options);
    return $question;
}

/** Returns ['before' => row, 'after' => row] for undo/logging. */
function update_question(
    PDO $pdo,
    int $id,
    int $examId,
    int $domainId,
    string $stem,
    array $options,
    string $explanation,
    string $difficulty,
    string $status,
    string $source
): array {
    $before = find_question($pdo, $id);
    if ($before === null) {
        throw new RuntimeException('Question not found.');
    }
    $stmt = $pdo->prepare(<<<'SQL'
        UPDATE questions
        SET exam_id = :exam_id, domain_id = :domain_id, stem = :stem,
            explanation = :explanation, difficulty = :difficulty, status = :status, source = :source
        WHERE id = :id
        RETURNING id, exam_id, domain_id, stem, explanation, difficulty, status, source
    SQL);
    $stmt->execute([
        'id' => $id, 'exam_id' => $examId, 'domain_id' => $domainId, 'stem' => $stem,
        'explanation' => $explanation, 'difficulty' => $difficulty,
        'status' => $status, 'source' => $source,
    ]);
    $after = $stmt->fetch();
    replace_question_options($pdo, $id, $options);
    return ['before' => $before, 'after' => $after];
}

/** Delete-and-reinsert inside the caller's transaction (build spec rule). */
function replace_question_options(PDO $pdo, int $questionId, array $options): void
{
    $pdo->prepare('DELETE FROM question_options WHERE question_id = :id')->execute(['id' => $questionId]);
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO question_options (question_id, option_text, is_correct, display_order)
        VALUES (:question_id, :text, :correct, :display_order)
    SQL);
    foreach (array_values($options) as $index => $option) {
        $stmt->execute([
            'question_id'   => $questionId,
            'text'          => $option['text'],
            'correct'       => $option['correct'] ? 't' : 'f',
            'display_order' => $index + 1,
        ]);
    }
}

function set_question_status(PDO $pdo, int $id, string $status): array
{
    if (!in_array($status, ['draft', 'active', 'retired'], true)) {
        throw new InvalidArgumentException('Bad status.');
    }
    $stmt = $pdo->prepare(<<<'SQL'
        UPDATE questions SET status = :status WHERE id = :id
        RETURNING id, status
    SQL);
    $stmt->execute(['id' => $id, 'status' => $status]);
    $row = $stmt->fetch();
    if ($row === false) {
        throw new RuntimeException('Question not found.');
    }
    return $row;
}

/** Drafts only, and never once the question has been drawn into a game. */
function delete_question(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare(<<<'SQL'
        DELETE FROM questions q
        WHERE q.id = :id AND q.status = 'draft'
          AND NOT EXISTS (SELECT 1 FROM game_questions gq WHERE gq.question_id = q.id)
    SQL);
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() === 1;
}

function find_domains_for_exam(PDO $pdo, int $examId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT id, name, weight_pct FROM domains
        WHERE exam_id = :exam_id ORDER BY display_order
    SQL);
    $stmt->execute(['exam_id' => $examId]);
    return $stmt->fetchAll();
}
