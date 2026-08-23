<?php
/**
 * Player stage: answer buttons (the phone screen). Vars: $game, $player, $gq, $version.
 * Stem lives on the host screen; buttons show option text (truncated at 60 chars).
 */
$pollUrl      = '/play/state?v=' . urlencode((string) $version);
$optionColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
$remaining    = (int) $gq['seconds_remaining'];
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#play-stage" hx-swap="outerHTML">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-soft-warning text-warning" id="play-question-number">
            Question <?= e($gq['position']) ?> / <?= e($game['question_count']) ?>
        </span>
        <span class="fs-4 fw-bold" id="play-question-countdown"><?= e($remaining) ?>s</span>
    </div>
    <div id="play-answer-buttons">
        <?php foreach ($gq['options'] as $index => $option): ?>
            <?php
            $text = (string) $option['option_text'];
            if (mb_strlen($text) > 60) {
                $text = mb_substr($text, 0, 57) . '…';
            }
            ?>
            <button type="button" id="play-answer-btn-<?= e($option['display_order']) ?>"
                    class="btn btn-<?= e($optionColors[$index] ?? 'secondary') ?> btn-lg w-100 py-4 mb-3 text-start"
                    hx-post="/play/answer" hx-vals='{"option_id": <?= (int) $option['id'] ?>}'
                    hx-target="#play-stage" hx-swap="outerHTML">
                <span class="fw-bold me-2"><?= e($option['display_order']) ?>.</span>
                <span><?= e($text) ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</div>
