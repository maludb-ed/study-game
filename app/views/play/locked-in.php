<?php /* Player stage: answered, waiting for reveal. Vars: $game, $player, $gq, $answer, $version. */
$pollUrl      = '/play/state?v=' . urlencode((string) $version);
$optionColors = [1 => 'danger', 2 => 'primary', 3 => 'warning', 4 => 'success', 5 => 'info', 6 => 'secondary'];
$picked       = $answer['picked'] ?? [];
if ($picked === [] && $answer['display_order'] !== null) {
    $picked = [['display_order' => $answer['display_order'], 'option_text' => $answer['option_text'] ?? '']];
}
$orders = array_map(static fn ($p) => (int) $p['display_order'], $picked);
$color  = $optionColors[$orders[0] ?? 0] ?? 'secondary';
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#play-stage" hx-swap="outerHTML">
    <h2 class="fs-16 fw-bolder mb-3" id="play-locked-stem" style="white-space: pre-line; word-break: break-word;"><?= e((string) $gq['stem']) ?></h2>
    <p class="fs-20 fw-bolder mb-3" id="play-locked-title">Locked in!</p>
    <p class="fs-14" id="play-locked-choice">
        <span class="wd-10 ht-10 bg-<?= e($color) ?> me-2 d-inline-block rounded-circle"></span>
        <?php if (count($orders) <= 1): ?>
            You picked option <?= e($orders[0] ?? '—') ?>.
        <?php else: ?>
            You locked in options <?= e(implode(', ', $orders)) ?>.
        <?php endif; ?>
    </p>
    <p class="fs-12 text-muted" id="play-locked-waiting">Waiting for everyone else…</p>
</div>
