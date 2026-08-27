<?php
/**
 * Player stage: the question + answer buttons (the phone screen). Vars: $game, $player, $gq, $version.
 * The stem is shown here as well as on the host screen; buttons show the FULL option text — never truncated.
 *
 * A question with 2+ correct options is MULTI-SELECT (all-or-nothing): the player ticks a
 * set and taps "Lock in". The selection form uses hx-preserve so the 1s countdown poll
 * (which self-swaps this stage) doesn't wipe the player's ticks mid-question. Single-correct
 * questions keep the instant single-tap for speed. is_correct is used server-side only.
 *
 * The first QUESTION_READ_SECONDS are reading time: the answer clock is pinned at its full
 * value while the reading seconds count down. Tapping during the window is allowed and is
 * scored as an instant answer.
 */
$pollUrl      = '/play/state?v=' . urlencode((string) $version);
$optionColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
$remaining    = (int) $gq['seconds_remaining'];
$reading      = (int) ($gq['reading_remaining'] ?? 0);
$correctCount = 0;
foreach ($gq['options'] as $o) { if ($o['is_correct']) { $correctCount++; } }
$multi = $correctCount > 1;
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#play-stage" hx-swap="outerHTML">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-soft-warning text-warning" id="play-question-number">
            Question <?= e($gq['position']) ?> / <?= e($game['question_count']) ?>
        </span>
        <span class="fs-4 fw-bold" id="play-question-countdown">
            <?php if ($reading > 0): ?>
                <span class="fs-13 text-info" id="play-question-reading">Reading — <?= e($reading) ?>s</span>
            <?php else: ?>
                <?= e($remaining) ?>s
            <?php endif; ?>
        </span>
    </div>
    <?php if ($gq['scenario_title'] !== null): ?>
        <p class="fs-12 text-info mb-2" id="play-question-scenario">
            <i class="feather-book-open me-1"></i><?= e((string) $gq['scenario_title']) ?>
        </p>
    <?php endif; ?>
    <h2 class="fs-16 fw-bolder mb-3" id="play-question-stem" style="white-space: pre-line; word-break: break-word;"><?= e((string) $gq['stem']) ?></h2>
    <?php if ($multi): ?>
        <p class="fs-13 text-info mb-3" id="play-multi-hint"><i class="feather-check-square me-1"></i>Select all that apply, then lock in.</p>
        <form id="play-answer-form" hx-preserve="true">
            <?php foreach ($gq['options'] as $index => $option): ?>
                <input type="checkbox" class="btn-check" name="option_ids[]" autocomplete="off"
                       id="play-choice-<?= e($option['display_order']) ?>" value="<?= (int) $option['id'] ?>">
                <label class="btn btn-outline-<?= e($optionColors[$index] ?? 'secondary') ?> btn-lg w-100 py-3 mb-3 text-start"
                       style="white-space: normal; word-break: break-word;"
                       for="play-choice-<?= e($option['display_order']) ?>">
                    <span class="fw-bold me-2"><?= e($option['display_order']) ?>.</span><?= e((string) $option['option_text']) ?>
                </label>
            <?php endforeach; ?>
        </form>
        <button type="button" id="play-lockin-btn" class="btn btn-primary btn-lg w-100 py-3"
                hx-post="/play/answer" hx-include="#play-answer-form"
                hx-target="#play-stage" hx-swap="outerHTML">
            <i class="feather-check me-2"></i><span>Lock in</span>
        </button>
    <?php else: ?>
        <div id="play-answer-buttons">
            <?php foreach ($gq['options'] as $index => $option): ?>
                <button type="button" id="play-answer-btn-<?= e($option['display_order']) ?>"
                        class="btn btn-<?= e($optionColors[$index] ?? 'secondary') ?> btn-lg w-100 py-4 mb-3 text-start"
                        style="white-space: normal; word-break: break-word;"
                        hx-post="/play/answer" hx-vals='{"option_id": <?= (int) $option['id'] ?>}'
                        hx-target="#play-stage" hx-swap="outerHTML">
                    <span class="fw-bold me-2"><?= e($option['display_order']) ?>.</span>
                    <span><?= e((string) $option['option_text']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
