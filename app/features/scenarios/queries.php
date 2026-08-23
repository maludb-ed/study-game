<?php
declare(strict_types=1);

const SCENARIOS_PAGE_SIZE = 25;

/** List with filters + pagination. Returns ['rows' => [...], 'total' => int]. */
function find_scenarios(PDO $pdo, int $examId = 0, string $status = '', int $page = 1): array
{
    $where  = [];
    $params = [];
    if ($examId > 0) { $where[] = 's.exam_id = :exam_id'; $params['exam_id'] = $examId; }
    if ($status !== '' && in_array($status, ['draft', 'active', 'retired'], true)) {
        $where[] = 's.status = :status';
        $params['status'] = $status;
    }
    $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT count(*) FROM scenarios s {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $page   = max(1, $page);
    $offset = ($page - 1) * SCENARIOS_PAGE_SIZE;
    $stmt = $pdo->prepare(<<<SQL
        SELECT s.id, s.title, s.status, s.updated_at, e.code AS exam_code,
               (SELECT count(*) FROM questions q WHERE q.scenario_id = s.id) AS question_count
        FROM scenarios s
        JOIN exams e ON e.id = s.exam_id
        {$whereSql}
        ORDER BY s.updated_at DESC
        LIMIT :limit OFFSET :offset
    SQL);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', SCENARIOS_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/** One scenario with its linked questions (id order = play order). */
function find_scenario(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT s.*, e.code AS exam_code, e.name AS exam_name, u.display_name AS author_name
        FROM scenarios s
        JOIN exams e ON e.id = s.exam_id
        LEFT JOIN users u ON u.id = s.created_by
        WHERE s.id = :id
    SQL);
    $stmt->execute(['id' => $id]);
    $scenario = $stmt->fetch();
    if ($scenario === false) {
        return null;
    }
    $qStmt = $pdo->prepare(<<<'SQL'
        SELECT q.id, q.stem, q.status, q.difficulty, d.name AS domain_name
        FROM questions q
        JOIN domains d ON d.id = q.domain_id
        WHERE q.scenario_id = :id
        ORDER BY q.id
    SQL);
    $qStmt->execute(['id' => $id]);
    $scenario['questions'] = $qStmt->fetchAll();
    return $scenario;
}

function insert_scenario(PDO $pdo, int $examId, string $title, string $body, string $status, int $createdBy): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO scenarios (exam_id, title, body, status, created_by)
        VALUES (:exam_id, :title, :body, :status, :created_by)
        RETURNING id, exam_id, title, body, status
    SQL);
    $stmt->execute([
        'exam_id' => $examId, 'title' => $title, 'body' => $body,
        'status' => $status, 'created_by' => $createdBy,
    ]);
    return $stmt->fetch();
}

/** Returns ['before' => row, 'after' => row]. */
function update_scenario(PDO $pdo, int $id, int $examId, string $title, string $body, string $status): array
{
    $before = find_scenario($pdo, $id);
    if ($before === null) {
        throw new RuntimeException('Scenario not found.');
    }
    $stmt = $pdo->prepare(<<<'SQL'
        UPDATE scenarios
        SET exam_id = :exam_id, title = :title, body = :body, status = :status
        WHERE id = :id
        RETURNING id, exam_id, title, body, status
    SQL);
    $stmt->execute(['id' => $id, 'exam_id' => $examId, 'title' => $title, 'body' => $body, 'status' => $status]);
    return ['before' => $before, 'after' => $stmt->fetch()];
}

function set_scenario_status(PDO $pdo, int $id, string $status): array
{
    if (!in_array($status, ['draft', 'active', 'retired'], true)) {
        throw new InvalidArgumentException('Bad status.');
    }
    $stmt = $pdo->prepare('UPDATE scenarios SET status = :status WHERE id = :id RETURNING id, status');
    $stmt->execute(['id' => $id, 'status' => $status]);
    $row = $stmt->fetch();
    if ($row === false) {
        throw new RuntimeException('Scenario not found.');
    }
    return $row;
}

/** Drafts only, and only when no question references the scenario. */
function delete_scenario(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare(<<<'SQL'
        DELETE FROM scenarios s
        WHERE s.id = :id AND s.status = 'draft'
          AND NOT EXISTS (SELECT 1 FROM questions q WHERE q.scenario_id = s.id)
    SQL);
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() === 1;
}

/** Draft+active scenarios of one exam — the question form's attach options. */
function find_scenarios_for_exam(PDO $pdo, int $examId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT id, title, status FROM scenarios
        WHERE exam_id = :exam_id AND status IN ('draft', 'active')
        ORDER BY title
    SQL);
    $stmt->execute(['exam_id' => $examId]);
    return $stmt->fetchAll();
}
