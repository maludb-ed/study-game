<?php
/**
 * Lobby holding screen. Vars: $game, $player, $liveCount, $version.
 * Root id (play-stage) is the poll region: hx-get /play/state?v=... every 2s, self-swap.
 */
$pollUrl = '/play/state?v=' . urlencode((string) $version);
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 2s" hx-target="#play-stage" hx-swap="outerHTML">
    <h2 class="fs-20 fw-bolder mb-3" id="play-lobby-title">You're in!</h2>
    <p class="fs-14" id="play-lobby-status">
        You're in as <strong><?= e($player['nickname']) ?></strong> — watch the host screen.
    </p>
    <p class="fs-12 text-muted" id="play-lobby-count">
        <?= e($liveCount) ?> player<?= $liveCount === 1 ? '' : 's' ?> in the lobby.
    </p>
</div>
