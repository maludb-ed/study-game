<?php /* Player stage: answered, waiting for reveal. Vars: $game, $player, $gq, $answer, $version. */
$pollUrl      = '/play/state?v=' . urlencode((string) $version);
$optionColors = [1 => 'danger', 2 => 'primary', 3 => 'warning', 4 => 'success', 5 => 'info', 6 => 'secondary'];
$color        = $optionColors[(int) $answer['display_order']] ?? 'secondary';
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#play-stage" hx-swap="outerHTML">
    <h2 class="fs-20 fw-bolder mb-3" id="play-locked-title">Locked in!</h2>
    <p class="fs-14" id="play-locked-choice">
        <span class="wd-10 ht-10 bg-<?= e($color) ?> me-2 d-inline-block rounded-circle"></span>
        You picked option <?= e($answer['display_order']) ?>.
    </p>
    <p class="fs-12 text-muted" id="play-locked-waiting">Waiting for everyone else…</p>
</div>
