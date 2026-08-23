<?php /* Player stage: own standing. Vars: $game, $player, $mine, $version. */
$pollUrl = '/play/state?v=' . urlencode((string) $version);
$suffix = static function (int $rank): string {
    if ($rank % 100 >= 11 && $rank % 100 <= 13) { return 'th'; }
    return ['th', 'st', 'nd', 'rd'][$rank % 10] ?? 'th';
};
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#play-stage" hx-swap="outerHTML">
    <?php if ($mine !== null): ?>
        <div class="avatar-text avatar-xl bg-primary mx-auto mb-3"><?= e($mine['rank']) ?></div>
        <h2 class="fs-20 fw-bolder mb-2 text-center" id="play-leaderboard-rank">
            You're <?= e($mine['rank']) ?><?= e($suffix((int) $mine['rank'])) ?> — <?= e(number_format((int) $mine['total'])) ?> pts
        </h2>
        <?php if ((int) $mine['current_streak'] >= 2): ?>
            <p class="fs-13 text-center text-warning"><i class="feather-zap me-1"></i>Streak of <?= e($mine['current_streak']) ?> — keep it going!</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="fs-14 text-center" id="play-leaderboard-rank">Scores are up on the host screen.</p>
    <?php endif; ?>
    <p class="fs-12 text-muted text-center" id="play-leaderboard-waiting">Next question is coming…</p>
</div>
