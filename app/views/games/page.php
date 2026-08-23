<?php
/**
 * Games list screen. Vars: $result ['rows','total'], $filters ['exam_id','page'], $exams.
 * Swaps into #page-content; the results region sub-swaps via #game-list-results.
 */
?>
<!-- [ page-header ] start -->
<div class="page-header" id="game-list-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Games</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Games</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="page-header-right-items">
            <div class="d-flex d-md-none">
                <a href="javascript:void(0)" class="page-header-right-close-toggle">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                <a href="/games/new" id="game-list-new-btn" class="btn btn-primary"
                   hx-get="/games/new" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/games/new">
                    <i class="feather-plus me-2"></i>
                    <span>New Game</span>
                </a>
            </div>
        </div>
        <div class="d-md-none d-flex align-items-center">
            <a href="javascript:void(0)" class="page-header-right-open-toggle">
                <i class="feather-align-right fs-20"></i>
            </a>
        </div>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="game-list-content" data-screen="game-list">
    <div class="row">
        <div class="col-lg-12">
            <form id="game-list-filters" class="d-flex flex-wrap gap-2 mb-3" action="/games/" method="get">
                <select name="exam_id" id="game-list-filter-exam" class="form-select w-auto"
                        hx-get="/games/" hx-target="#game-list-results" hx-swap="outerHTML"
                        hx-include="#game-list-filters">
                    <option value="">All exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?= e($exam['id']) ?>" <?= (int) $filters['exam_id'] === (int) $exam['id'] ? 'selected' : '' ?>>
                            <?= e($exam['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?= view('games/partials/table.php', ['result' => $result, 'filters' => $filters]) ?>
    </div>
</div>
<!-- [ Main Content ] end -->
