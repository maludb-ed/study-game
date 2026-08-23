<?php
/**
 * Player stage: personal result. Vars: $game, $player, $gq, $answer (null = missed), $total, $version.
 */
$pollUrl = '/play/state?v=' . urlencode((string) $version);
$correctOption = null;
foreach ($gq['options'] as $option) {
    if ($option['is_correct']) {
        $correctOption = $option;
        break;
    }
}
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#play-stage" hx-swap="outerHTML">
    <?php if ($answer === null): ?>
        <div class="avatar-text avatar-xl bg-secondary mx-auto mb-3"><i class="feather-clock"></i></div>
        <h2 class="fs-20 fw-bolder mb-2 text-center" id="play-reveal-title">Time's up — 0 pts</h2>
    <?php elseif ($answer['is_correct']): ?>
        <div class="avatar-text avatar-xl bg-success mx-auto mb-3"><i class="feather-check"></i></div>
        <h2 class="fs-20 fw-bolder mb-2 text-center" id="play-reveal-title">Correct! +<?= e(number_format((int) $answer['points_awarded'])) ?> pts</h2>
        <?php if ((int) $answer['streak_after'] >= 2): ?>
            <p class="fs-13 text-center text-warning" id="play-reveal-streak">
                <i class="feather-zap me-1"></i>Streak of <?= e($answer['streak_after']) ?>!
            </p>
        <?php endif; ?>
    <?php else: ?>
        <div class="avatar-text avatar-xl bg-danger mx-auto mb-3"><i class="feather-x"></i></div>
        <h2 class="fs-20 fw-bolder mb-2 text-center" id="play-reveal-title">Not this time</h2>
    <?php endif; ?>
    <?php if ($correctOption !== null && ($answer === null || !$answer['is_correct'])): ?>
        <p class="fs-13 text-center" id="play-reveal-correct">
            Correct answer: <strong><?= e($correctOption['option_text']) ?></strong>
        </p>
    <?php endif; ?>
    <p class="fs-12 text-muted text-center" id="play-reveal-total">Your total: <?= e(number_format((int) $total)) ?> pts</p>
</div>
