<?php
declare(strict_types=1);

/**
 * Ephemeral practice-mode state, held in the PHP session (no DB persistence).
 * One run at a time per session: a shuffled queue of active question ids for an exam,
 * a cursor, and a running score. Rationales are revealed on every question.
 */

/** Shuffled active question ids for an exam (the practice queue). */
function practice_active_question_ids(PDO $pdo, int $examId): array
{
    $stmt = $pdo->prepare("SELECT id FROM questions WHERE exam_id = :e AND status = 'active'");
    $stmt->execute(['e' => $examId]);
    $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
    shuffle($ids);
    return $ids;
}

function practice_start(int $examId, string $examLabel, array $questionIds): void
{
    $_SESSION['practice'] = [
        'exam_id'      => $examId,
        'exam_label'   => $examLabel,
        'queue'        => array_values($questionIds),
        'pos'          => 0,
        'answered'     => 0,
        'correct'      => 0,
        'done_current' => false,   // has the question at the cursor been answered yet?
    ];
}

function practice_state(): ?array
{
    return $_SESSION['practice'] ?? null;
}

function practice_total(): int
{
    return count($_SESSION['practice']['queue'] ?? []);
}

/** Current question id, or null when the run is finished / not started. */
function practice_current_qid(): ?int
{
    $s = $_SESSION['practice'] ?? null;
    if ($s === null || $s['pos'] >= count($s['queue'])) {
        return null;
    }
    return (int) $s['queue'][$s['pos']];
}

function practice_is_finished(): bool
{
    $s = $_SESSION['practice'] ?? null;
    return $s !== null && $s['pos'] >= count($s['queue']);
}

/** Record the current question's result once (idempotent for the current cursor). */
function practice_record(bool $correct): void
{
    if (empty($_SESSION['practice']) || $_SESSION['practice']['done_current']) {
        return;
    }
    $_SESSION['practice']['answered']++;
    if ($correct) {
        $_SESSION['practice']['correct']++;
    }
    $_SESSION['practice']['done_current'] = true;
}

function practice_advance(): void
{
    if (empty($_SESSION['practice'])) {
        return;
    }
    $_SESSION['practice']['pos']++;
    $_SESSION['practice']['done_current'] = false;
}

function practice_clear(): void
{
    unset($_SESSION['practice']);
}
