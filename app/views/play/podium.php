<?php
/**
 * Player stage: final result. Terminal (returned with 286 — polling stops). No poll attrs.
 * Vars: $game, $player, $mine.
 */
$suffix = static function (int $rank): string {
    if ($rank % 100 >= 11 && $rank % 100 <= 13) { return 'th'; }
    return ['th', 'st', 'nd', 'rd'][$rank % 10] ?? 'th';
};
$rank = $mine !== null ? (int) $mine['final_rank'] : 0;
$medals = [1 => 'warning', 2 => 'secondary', 3 => 'danger'];
?>
<div id="play-stage">
    <?php if ($mine !== null): ?>
        <div class="avatar-text avatar-xl bg-<?= e($medals[$rank] ?? 'primary') ?> mx-auto mb-3"><?= e($rank) ?></div>
        <h2 class="fs-20 fw-bolder mb-2 text-center" id="play-podium-title">
            <?= $rank === 1 ? 'Champion!' : 'You finished ' . e($rank) . e($suffix($rank)) ?>
        </h2>
        <p class="fs-14 text-center" id="play-podium-score"><?= e(number_format((int) $mine['final_score'])) ?> points</p>
    <?php else: ?>
        <h2 class="fs-20 fw-bolder mb-2 text-center" id="play-podium-title">Game over</h2>
    <?php endif; ?>
    <a href="/join" id="play-podium-join-link" class="btn btn-primary w-100 mt-3">Join another game</a>
</div>
