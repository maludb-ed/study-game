<?php
/**
 * Readiness banner (analytics-member). Vars: $readiness — member_readiness() result
 * (score 100-1000, coverage_pct, band_status/band_label). Bootstrap progress bar only,
 * no chart libraries, per the build spec's out-of-scope list.
 */
$score    = (int) $readiness['score'];
$scorePct = (int) round((($score - 100) / 900) * 100); // 100-1000 -> 0-100% bar fill
$scorePct = max(0, min(100, $scorePct));
$passPct  = (int) round((720 - 100) / 900 * 100);        // the 720 bar, on the same 0-100 scale
?>
<div class="col-lg-12" id="analytics-member-banner">
    <div class="card stretch stretch-full">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <p class="text-muted fs-12 mb-1">Weighted Readiness Score</p>
                    <h3 class="fw-bold mb-0"><?= e($score) ?> <small class="text-muted fs-14">/ 1000</small></h3>
                </div>
                <span class="badge bg-soft-<?= e($readiness['band_status']) ?> text-<?= e($readiness['band_status']) ?> fs-13">
                    <?= e($readiness['band_label']) ?>
                </span>
            </div>
            <div class="progress ht-8 mb-1" style="position: relative;">
                <div class="progress-bar bg-<?= e($readiness['band_status']) ?>" role="progressbar" style="width: <?= $scorePct ?>%"></div>
                <div class="position-absolute top-0 bottom-0 bg-dark" style="left: <?= $passPct ?>%; width: 2px;" title="720 pass bar"></div>
            </div>
            <p class="fs-11 text-muted mb-3">Passing bar: 720</p>
            <p class="fs-12 text-muted mb-0">
                Blueprint coverage: <?= e($readiness['coverage_pct']) ?>% of the exam's weighted domains seen
                (need ≥60% for a reliable score). Based on claimed answers only.
            </p>
        </div>
    </div>
</div>
