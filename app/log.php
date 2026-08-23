<?php
declare(strict_types=1);

/**
 * Activity memory emission point — every screen entry, record change, and game
 * action writes a row (new-app skill: logging is not optional).
 * $opts: actor_type, actor_id, actor_label, screen, entity, entity_id, game_id,
 *        before, after, details (arrays are JSON-encoded).
 */
function log_activity(PDO $pdo, string $action, array $opts = []): void
{
    $actorType  = $opts['actor_type'] ?? null;
    $actorId    = $opts['actor_id'] ?? null;
    $actorLabel = $opts['actor_label'] ?? null;

    if ($actorType === null) {
        $user = function_exists('current_user') ? current_user() : null;
        if ($user === null && function_exists('acting_via_action_token') && acting_via_action_token()) {
            // Assistant acting AS a user (action token): attribute to that user.
            $stmt = $pdo->prepare('SELECT id, display_name FROM users WHERE id = :id');
            $stmt->execute(['id' => $GLOBALS['__action_token_user_id']]);
            $user = $stmt->fetch() ?: null;
        }
        if ($user !== null) {
            $actorType  = 'user';
            $actorId    = (int) $user['id'];
            $actorLabel = $user['display_name'];
        } else {
            $actorType = 'system';
        }
    }

    $stmt = $pdo->prepare(<<<'SQL'
        INSERT INTO activity_log
            (actor_type, actor_id, actor_label, action, screen, entity, entity_id,
             game_id, before_data, after_data, details, ip)
        VALUES
            (:actor_type, :actor_id, :actor_label, :action, :screen, :entity, :entity_id,
             :game_id, :before_data, :after_data, :details, :ip)
    SQL);
    $stmt->execute([
        'actor_type'  => $actorType,
        'actor_id'    => $actorId,
        'actor_label' => $actorLabel ?? '',
        'action'      => $action,
        'screen'      => $opts['screen'] ?? '',
        'entity'      => $opts['entity'] ?? '',
        'entity_id'   => $opts['entity_id'] ?? null,
        'game_id'     => $opts['game_id'] ?? null,
        'before_data' => isset($opts['before']) ? json_encode($opts['before']) : null,
        'after_data'  => isset($opts['after']) ? json_encode($opts['after']) : null,
        'details'     => isset($opts['details']) ? json_encode($opts['details']) : null,
        'ip'          => client_ip(),
    ]);
}

function log_screen_view(PDO $pdo, string $screen): void
{
    log_activity($pdo, 'screen_view', ['screen' => $screen]);
}
