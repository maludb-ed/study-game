<?php
/**
 * Host stage: live question (the projected screen). Vars: $game, $gq, $answered, $live, $version.
 * Root self-swaps via the 1s poll; the version string ticks every second during questions.
 *
 * The first QUESTION_READ_SECONDS are reading time: the answer clock is pinned at its full
 * value and the countdown shows the reading seconds instead. Answers are accepted throughout
 * (an answer inside the window scores as instant), so nothing here is disabled.
 */
$id           = (int) $game['id'];
$pollUrl      = '/games/' . $id . '/host-state?v=' . urlencode((string) $version);
$remaining    = (int) $gq['seconds_remaining'];
$reading      = (int) ($gq['reading_remaining'] ?? 0);
$seconds      = (int) $game['seconds_per_question'];
$pct          = $seconds > 0 ? (int) round(100 * $remaining / $seconds) : 0;
$optionColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
?>
<div id="game-host-stage"
     hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#game-host-stage" hx-swap="outerHTML">
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="badge bg-soft-warning text-warning" id="game-host-question-number">
                Question <?= e($gq['position']) ?> / <?= e($game['question_count']) ?>
            </span>
            <span class="fs-13 text-muted" id="game-host-answered-count">
                <?= e($answered) ?> of <?= e($live) ?> answered
            </span>
            <span class="fs-4 fw-bold" id="game-host-countdown">
                <?php if ($reading > 0): ?>
                    <span class="fs-13 text-info" id="game-host-reading">Reading — <?= e($reading) ?>s</span>
                <?php else: ?>
                    <?= e($remaining) ?>s
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="progress ht-3 mb-4" id="game-host-countdown-bar">
                <div class="progress-bar bg-<?= $reading > 0 ? 'info' : ($remaining <= 5 ? 'danger' : 'primary') ?>" role="progressbar"
                     style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <h2 class="fs-2 fw-bold text-center my-4" id="game-host-question-stem"><?= e($gq['stem']) ?></h2>
            <div class="row" id="game-host-options">
                <?php foreach ($gq['options'] as $index => $option): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card border h-100 mb-0" id="game-host-option-<?= e($option['display_order']) ?>">
                            <div class="card-body d-flex align-items-center py-3">
                                <div class="avatar-text avatar-md bg-<?= e($optionColors[$index] ?? 'secondary') ?> me-3">
                                    <?= e($option['display_order']) ?>
                                </div>
                                <span class="fs-5"><?= e($option['option_text']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="button" id="game-host-abort-btn" class="btn btn-sm btn-light-brand text-danger"
                    hx-post="/games/<?= $id ?>/abort" hx-target="#game-host-stage" hx-swap="outerHTML"
                    hx-confirm="Abort this game? This cannot be undone.">
                <i class="feather-x-circle me-2"></i>
                <span>Abort</span>
            </button>
        </div>
    </div>
</div>
