<?php
/**
 * Host stage: reveal — distribution, correct answer, THE EXPLANATION (the teaching
 * moment, always shown). Vars: $game, $gq, $distribution, $live, $version.
 */
$id           = (int) $game['id'];
$pollUrl      = '/games/' . $id . '/host-state?v=' . urlencode((string) $version);
$optionColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
?>
<div id="game-host-stage"
     hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#game-host-stage" hx-swap="outerHTML">
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="badge bg-soft-info text-info" id="game-host-reveal-number">
                Question <?= e($gq['position']) ?> / <?= e($game['question_count']) ?> — results
            </span>
            <button type="button" id="game-host-next-btn" class="btn btn-primary"
                    hx-post="/games/<?= $id ?>/advance" hx-vals='{"expected_state": "reveal"}'
                    hx-target="#game-host-stage" hx-swap="outerHTML">
                <span>Leaderboard</span>
                <i class="feather-arrow-right ms-2"></i>
            </button>
        </div>
        <div class="card-body">
            <h4 class="fs-4 fw-bold text-center mb-4" id="game-host-reveal-stem"><?= e($gq['stem']) ?></h4>
            <div id="game-host-distribution">
                <?php foreach ($distribution as $index => $option): ?>
                    <div class="d-flex align-items-center mb-3" id="game-host-dist-<?= e($option['display_order']) ?>">
                        <div class="avatar-text avatar-sm bg-<?= e($optionColors[$index] ?? 'secondary') ?> me-3">
                            <?= e($option['display_order']) ?>
                        </div>
                        <div class="flex-fill">
                            <div class="d-flex justify-content-between">
                                <span class="fs-13 <?= $option['is_correct'] ? 'fw-bold' : '' ?>">
                                    <?= e($option['option_text']) ?>
                                    <?php if ($option['is_correct']): ?>
                                        <span class="badge bg-soft-success text-success ms-2">Correct</span>
                                    <?php endif; ?>
                                </span>
                                <span class="fs-13 text-muted"><?= e($option['picks']) ?></span>
                            </div>
                            <div class="progress ht-3 mt-1">
                                <div class="progress-bar bg-<?= $option['is_correct'] ? 'success' : 'secondary' ?>" role="progressbar"
                                     style="width: <?= (int) $option['pct'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card bg-gray-200 border-0 mt-4 mb-0" id="game-host-explanation">
                <div class="card-body">
                    <h6 class="fs-13 fw-bold"><i class="feather-book-open me-2"></i>Why</h6>
                    <p class="fs-13 mb-0"><?= e($gq['explanation']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
