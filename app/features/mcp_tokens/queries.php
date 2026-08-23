<?php
declare(strict_types=1);

function find_mcp_tokens(PDO $pdo): array
{
    return $pdo->query(<<<'SQL'
        SELECT t.id, t.label, t.server, t.created_at, t.revoked_at, t.last_used_at,
               u.display_name AS created_by_name, t.created_by
        FROM mcp_tokens t
        LEFT JOIN users u ON u.id = t.created_by
        ORDER BY t.created_at DESC
    SQL)->fetchAll();
}

/** Returns ['row' => .., 'plaintext' => ..] — the plaintext is shown exactly once. */
function insert_mcp_token(PDO $pdo, string $label, string $server, int $createdBy): array
{
    $plaintext = 'sgmcp_' . bin2hex(random_bytes(24));
    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO mcp_tokens (label, server, token_hash, created_by)
        VALUES (:label, :server, :hash, :created_by)
        RETURNING id, label, server, created_at
    SQL);
    $stmt->execute([
        'label' => $label, 'server' => $server,
        'hash' => hash('sha256', $plaintext), 'created_by' => $createdBy,
    ]);
    return ['row' => $stmt->fetch(), 'plaintext' => $plaintext];
}

function revoke_mcp_token(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('UPDATE mcp_tokens SET revoked_at = now() WHERE id = :id AND revoked_at IS NULL');
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() === 1;
}

function find_mcp_token(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, label, server, created_by, revoked_at FROM mcp_tokens WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}
