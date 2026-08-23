<?php
/**
 * Host console stage: div#game-host-stage — player list + Start + Abort, or a terminal
 * notice. Returned whole by host.php (initial), host-state.php (1s poll), kick.php,
 * abort.php, and advance.php (S2 placeholder). Vars: $game, $players, $version, $notice (optional).
 */
$id          = (int) $game['id'];
$isTerminal  = in_array($game['state'], GAMES_TERMINAL_STATES, true);
$livePlayers = array_values(array_filter($players, static fn (array $p): bool => $p['kicked_at'] === null));
$pollUrl     = '/games/' . $id . '/host-state?v=' . urlencode((string) $version);
?>
<div id="game-host-stage"
     <?php if (!$isTerminal): ?>
     hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#game-host-stage" hx-swap="outerHTML"
     <?php endif; ?>>
    <?php if (!empty($notice)): ?>
        <div class="alert alert-info fs-12" id="game-host-notice"><?= e($notice) ?></div>
    <?php endif; ?>
    <?php if ($isTerminal): ?>
        <div class="alert alert-<?= $game['state'] === 'aborted' ? 'danger' : 'success' ?> fs-12" id="game-host-terminal-notice">
            <?= $game['state'] === 'aborted' ? 'This game was aborted.' : 'This game has ended.' ?>
        </div>
    <?php else: ?>
        <div class="card stretch stretch-full">
            <div class="card-body text-center py-5">
                <p class="fs-13 text-muted mb-1" id="game-host-exam-label">
                    <?= e($game['exam_code']) ?> — <?= e($game['question_count']) ?> questions, <?= e($game['seconds_per_question']) ?>s each
                </p>
                <p class="text-muted mb-2">Game PIN</p>
                <div class="display-1 fw-bolder" id="game-host-pin"><?= e($game['pin']) ?></div>
                <p class="fs-16 mt-3" id="game-host-join-url">
                    Go to <strong><?= e(config('APP_URL')) ?>/join</strong>
                </p>
            </div>
        </div>
        <div class="card stretch stretch-full">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Players <small class="text-muted">(<?= count($livePlayers) ?>)</small></h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="game-host-players-list">
                    <?php if ($players === []): ?>
                        <li class="list-group-item text-muted text-center py-4" id="game-host-players-empty">Waiting for players to join…</li>
                    <?php endif; ?>
                    <?php foreach ($players as $player): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between" id="game-host-player-<?= e($player['id']) ?>">
                            <span<?= $player['kicked_at'] !== null ? ' class="text-muted text-decoration-line-through"' : '' ?>>
                                <?= e($player['nickname']) ?>
                            </span>
                            <?php if ($player['kicked_at'] !== null): ?>
                                <span class="badge bg-soft-secondary text-secondary">Kicked</span>
                            <?php else: ?>
                                <button type="button" id="game-host-player-<?= e($player['id']) ?>-kick-btn" class="btn btn-sm btn-light-brand text-danger"
                                        hx-post="/games/<?= $id ?>/kick" hx-vals='{"player_id": <?= (int) $player['id'] ?>}'
                                        hx-target="#game-host-stage" hx-swap="outerHTML"
                                        hx-confirm="Remove <?= e($player['nickname']) ?> from the game?">
                                    Kick
                                </button>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="button" id="game-host-start-btn" class="btn btn-primary" <?= $livePlayers === [] ? 'disabled' : '' ?>
                        hx-post="/games/<?= $id ?>/advance" hx-vals='{"expected_state": "lobby"}'
                        hx-target="#game-host-stage" hx-swap="outerHTML">
                    <i class="feather-play me-2"></i>
                    <span>Start</span>
                </button>
                <button type="button" id="game-host-abort-btn" class="btn btn-light-brand text-danger"
                        hx-post="/games/<?= $id ?>/abort" hx-target="#game-host-stage" hx-swap="outerHTML"
                        hx-confirm="Abort this game? This cannot be undone.">
                    <i class="feather-x-circle me-2"></i>
                    <span>Abort</span>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>
