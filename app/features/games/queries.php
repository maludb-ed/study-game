<?php
declare(strict_types=1);

const GAMES_PAGE_SIZE = 25;

/** States that no longer accept play/host actions (report is out of scope for S2). */
const GAMES_TERMINAL_STATES = ['ended', 'aborted'];

/** List with an exam filter + pagination. Returns ['rows' => [...], 'total' => int]. */
function find_games(PDO $pdo, int $examId = 0, int $page = 1): array
{
    $where  = [];
    $params = [];
    if ($examId > 0) {
        $where[] = 'g.exam_id = :exam_id';
        $params['exam_id'] = $examId;
    }
    $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT count(*) FROM games g {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $page   = max(1, $page);
    $offset = ($page - 1) * GAMES_PAGE_SIZE;
    $stmt = $pdo->prepare(<<<SQL
        SELECT g.id, g.created_at, g.state, g.pin, e.code AS exam_code, u.display_name AS host_name,
               (SELECT count(*) FROM game_players gp WHERE gp.game_id = g.id AND gp.kicked_at IS NULL) AS player_count
        FROM games g
        JOIN exams e ON e.id = g.exam_id
        JOIN users u ON u.id = g.host_user_id
        {$whereSql}
        ORDER BY g.created_at DESC
        LIMIT :limit OFFSET :offset
    SQL);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', GAMES_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/** One game, joined with its exam + host for display. */
function find_game(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT g.*, e.code AS exam_code, e.name AS exam_name, u.display_name AS host_name
        FROM games g
        JOIN exams e ON e.id = g.exam_id
        JOIN users u ON u.id = g.host_user_id
        WHERE g.id = :id
    SQL);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** A live game (not ended/aborted) by PIN — the join screen's lookup. */
function find_game_by_pin(PDO $pdo, string $pin): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT g.*, e.code AS exam_code, e.name AS exam_name
        FROM games g
        JOIN exams e ON e.id = g.exam_id
        WHERE g.pin = :pin AND g.state NOT IN ('ended', 'aborted')
    SQL);
    $stmt->execute(['pin' => $pin]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/**
 * Version stamp for the polling contract: extract(epoch from updated_at) || '-' || live player count.
 * Bumps on both a games-row update AND a lobby join/kick (which only touches game_players).
 */
function find_game_version(PDO $pdo, int $id): ?string
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT extract(epoch FROM g.updated_at)::bigint AS epoch,
               (SELECT count(*) FROM game_players gp WHERE gp.game_id = g.id AND gp.kicked_at IS NULL) AS live_players
        FROM games g
        WHERE g.id = :id
    SQL);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : ($row['epoch'] . '-' . $row['live_players']);
}

/**
 * Creates the game row and performs THE DRAW (largest-remainder domain targets, per-domain
 * ordering by times_played/miss_rate/random, cross-domain backfill, final shuffle) in one
 * transaction. PIN is a random 6-digit string, retried on collision against the partial
 * unique index (pin unique among live games only).
 * Throws DomainException('insufficient_bank') when the exam's active bank can't fill the game.
 * Returns the game row (RETURNING *) plus 'draw' => ['targets' => [...], 'shortfalls' => [...]].
 */
function insert_game(
    PDO $pdo,
    int $examId,
    int $hostUserId,
    int $questionCount,
    int $seconds,
    bool $streakBonus
): array {
    $domainStmt = $pdo->prepare(<<<'SQL'
        SELECT id, name, weight_pct FROM domains
        WHERE exam_id = :exam_id ORDER BY weight_pct DESC, display_order
    SQL);
    $domainStmt->execute(['exam_id' => $examId]);
    $domains = $domainStmt->fetchAll();

    $totalActiveStmt = $pdo->prepare("SELECT count(*) FROM questions WHERE exam_id = :exam_id AND status = 'active'");
    $totalActiveStmt->execute(['exam_id' => $examId]);
    $totalActive = (int) $totalActiveStmt->fetchColumn();

    if ($domains === [] || $totalActive < $questionCount) {
        throw new DomainException('insufficient_bank');
    }

    // Step 1: largest-remainder target per domain (floor + top-up by fractional remainder).
    $targets    = [];
    $remainders = [];
    $sumFloor   = 0;
    foreach ($domains as $domain) {
        $raw   = ((float) $domain['weight_pct'] / 100) * $questionCount;
        $floor = (int) floor($raw);
        $targets[(int) $domain['id']]    = $floor;
        $remainders[(int) $domain['id']] = $raw - $floor;
        $sumFloor += $floor;
    }
    $remaining = $questionCount - $sumFloor;
    $byRemainder = array_keys($remainders);
    usort($byRemainder, static fn ($a, $b) => $remainders[$b] <=> $remainders[$a]);
    for ($i = 0; $i < $remaining; $i++) {
        $targets[$byRemainder[$i % count($byRemainder)]]++;
    }

    // Step 2: per-domain candidate pool, ordered times_played ASC, miss_rate DESC, random().
    $candidateStmt = $pdo->prepare(<<<'SQL'
        SELECT q.id,
               COALESCE(stats.times_played, 0) AS times_played,
               COALESCE(stats.miss_rate, 0)    AS miss_rate
        FROM questions q
        LEFT JOIN LATERAL (
            SELECT count(gq.id) AS times_played,
                   CASE WHEN count(a.id) = 0 THEN 0
                        ELSE count(a.id) FILTER (WHERE NOT a.is_correct)::float / count(a.id)
                   END AS miss_rate
            FROM game_questions gq
            LEFT JOIN answers a ON a.game_question_id = gq.id
            WHERE gq.question_id = q.id
        ) stats ON true
        WHERE q.domain_id = :domain_id AND q.status = 'active'
        ORDER BY times_played ASC, miss_rate DESC, random()
    SQL);

    $candidatesByDomain = [];
    foreach ($domains as $domain) {
        $candidateStmt->execute(['domain_id' => $domain['id']]);
        $candidatesByDomain[(int) $domain['id']] = $candidateStmt->fetchAll();
    }

    // Step 3: take target[domain] from each domain; record shortfalls.
    $selected   = []; // question_id => domain_id, insertion order = selection order
    $shortfalls = []; // domain name => shortfall count
    foreach ($domains as $domain) {
        $domainId = (int) $domain['id'];
        $target   = $targets[$domainId];
        $take     = array_slice($candidatesByDomain[$domainId], 0, $target);
        foreach ($take as $row) {
            $selected[(int) $row['id']] = $domainId;
        }
        $shortfall = $target - count($take);
        if ($shortfall > 0) {
            $shortfalls[$domain['name']] = $shortfall;
        }
    }

    // Cross-domain backfill, in weight order (domains already sorted weight_pct DESC).
    $stillNeeded = $questionCount - count($selected);
    if ($stillNeeded > 0) {
        foreach ($domains as $domain) {
            if ($stillNeeded <= 0) {
                break;
            }
            $domainId = (int) $domain['id'];
            foreach ($candidatesByDomain[$domainId] as $row) {
                if ($stillNeeded <= 0) {
                    break;
                }
                $qid = (int) $row['id'];
                if (isset($selected[$qid])) {
                    continue;
                }
                $selected[$qid] = $domainId;
                $stillNeeded--;
            }
        }
    }

    if (count($selected) < $questionCount) {
        // The total-active precheck above should make this unreachable.
        throw new DomainException('insufficient_bank');
    }

    // Step 4: final shuffle.
    $questionIds = array_keys($selected);
    shuffle($questionIds);

    // Game row + draw insert together; PIN collisions retry the whole attempt.
    $attempts = 0;
    while (true) {
        $attempts++;
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        try {
            $pdo->beginTransaction();
            $gameStmt = $pdo->prepare(<<<'SQL'
                INSERT INTO games (exam_id, host_user_id, pin, question_count, seconds_per_question, streak_bonus)
                VALUES (:exam_id, :host_user_id, :pin, :question_count, :seconds, :streak_bonus)
                RETURNING *
            SQL);
            $gameStmt->execute([
                'exam_id' => $examId, 'host_user_id' => $hostUserId, 'pin' => $pin,
                'question_count' => $questionCount, 'seconds' => $seconds,
                'streak_bonus' => $streakBonus ? 't' : 'f',
            ]);
            $game = $gameStmt->fetch();

            $gqStmt = $pdo->prepare(<<<'SQL'
                INSERT INTO game_questions (game_id, question_id, position)
                VALUES (:game_id, :question_id, :position)
            SQL);
            foreach ($questionIds as $index => $questionId) {
                $gqStmt->execute(['game_id' => $game['id'], 'question_id' => $questionId, 'position' => $index + 1]);
            }
            $pdo->commit();
            break;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $isPinConflict = $exception->getCode() === '23505'
                && str_contains($exception->getMessage(), 'games_active_pin_uniq');
            if ($isPinConflict && $attempts < 10) {
                continue;
            }
            throw $exception;
        }
    }

    $game['draw'] = ['targets' => $targets, 'shortfalls' => $shortfalls];
    return $game;
}

function insert_game_player(PDO $pdo, int $gameId, string $nickname, ?int $userId, string $token): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO game_players (game_id, user_id, nickname, player_token)
        VALUES (:game_id, :user_id, :nickname, :token)
        RETURNING id, game_id, user_id, nickname, player_token, joined_at, kicked_at
    SQL);
    $stmt->execute(['game_id' => $gameId, 'user_id' => $userId, 'nickname' => $nickname, 'token' => $token]);
    return $stmt->fetch();
}

/** All players (including kicked, for host visibility), joined order. */
function find_game_players(PDO $pdo, int $gameId): array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT id, game_id, user_id, nickname, joined_at, kicked_at
        FROM game_players
        WHERE game_id = :game_id
        ORDER BY joined_at ASC
    SQL);
    $stmt->execute(['game_id' => $gameId]);
    return $stmt->fetchAll();
}

/** Rejoin key lookup — resolves the public player from their cookie token. */
function find_game_player_by_token(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT gp.id, gp.game_id, gp.user_id, gp.nickname, gp.joined_at, gp.kicked_at
        FROM game_players gp
        WHERE gp.player_token = :token
    SQL);
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Case-insensitive nickname collision check among non-kicked players of a game. */
function find_game_player_by_nickname(PDO $pdo, int $gameId, string $nickname): ?array
{
    $stmt = $pdo->prepare(<<<'SQL'
        SELECT id FROM game_players
        WHERE game_id = :game_id AND kicked_at IS NULL AND lower(nickname) = lower(:nickname)
    SQL);
    $stmt->execute(['game_id' => $gameId, 'nickname' => $nickname]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function kick_game_player(PDO $pdo, int $gameId, int $playerId): bool
{
    $stmt = $pdo->prepare(<<<'SQL'
        UPDATE game_players SET kicked_at = now()
        WHERE id = :player_id AND game_id = :game_id AND kicked_at IS NULL
    SQL);
    $stmt->execute(['player_id' => $playerId, 'game_id' => $gameId]);
    return $stmt->rowCount() === 1;
}

function abort_game(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare(<<<'SQL'
        UPDATE games SET state = 'aborted', ended_at = now()
        WHERE id = :id AND state NOT IN ('ended', 'aborted')
    SQL);
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() === 1;
}
