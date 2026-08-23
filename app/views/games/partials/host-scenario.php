<?php
/**
 * Host stage: scenario intro (the projected reading screen). Clock is stopped.
 * Vars: $game, $gq (with scenario_title/scenario_body), $version.
 */
$id      = (int) $game['id'];
$pollUrl = '/games/' . $id . '/host-state?v=' . urlencode((string) $version);
?>
<div id="game-host-stage"
     hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#game-host-stage" hx-swap="outerHTML">
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="badge bg-soft-info text-info" id="game-host-scenario-badge">
                Scenario — question <?= e($gq['position']) ?> / <?= e($game['question_count']) ?> up next
            </span>
            <button type="button" id="game-host-scenario-next-btn" class="btn btn-primary"
                    hx-post="/games/<?= $id ?>/advance" hx-vals='{"expected_state": "scenario_intro"}'
                    hx-target="#game-host-stage" hx-swap="outerHTML">
                <span>Start the Question</span>
                <i class="feather-arrow-right ms-2"></i>
            </button>
        </div>
        <div class="card-body">
            <h2 class="fs-2 fw-bold text-center my-4" id="game-host-scenario-title"><?= e($gq['scenario_title']) ?></h2>
            <p class="fs-4 text-center mb-4" id="game-host-scenario-body"><?= nl2br(e($gq['scenario_body'])) ?></p>
            <p class="fs-13 text-muted text-center mb-0">The timer starts when the host continues — take your time reading.</p>
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
