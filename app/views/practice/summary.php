<?php
/** Practice summary. Vars: $examId, $examLabel, $total, $correct. */
$pct = $total > 0 ? (int) round(100 * $correct / $total) : 0;
?>
<div class="main-content" id="practice-summary">
    <div class="card stretch stretch-full">
        <div class="card-body text-center py-5">
            <div class="avatar-text avatar-xl bg-<?= $pct >= 70 ? 'success' : ($pct >= 40 ? 'warning' : 'danger') ?> mx-auto mb-3">
                <i class="feather-award"></i>
            </div>
            <h2 class="fs-2 fw-bolder mb-1">Practice complete</h2>
            <p class="text-muted mb-4"><?= e($examLabel) ?></p>
            <h3 class="display-6 fw-bold mb-1"><?= (int) $correct ?> / <?= (int) $total ?></h3>
            <p class="fs-5 text-muted mb-4"><?= $pct ?>% correct</p>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-primary" id="practice-restart-btn"
                        hx-post="/practice/start" hx-vals='{"exam_id": <?= (int) $examId ?>}'
                        hx-target="#page-content" hx-swap="innerHTML">
                    <i class="feather-refresh-cw me-2"></i><span>Practice again</span>
                </button>
                <a href="/practice" class="btn btn-light-brand" id="practice-back-btn"
                   hx-get="/practice" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/practice">
                    <span>Choose another exam</span>
                </a>
            </div>
        </div>
    </div>
</div>
