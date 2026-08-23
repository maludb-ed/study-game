<?php
/** Scenarios list screen. Vars: $result, $filters ['exam_id','status','page'], $exams. */
?>
<!-- [ page-header ] start -->
<div class="page-header" id="scenario-list-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Scenarios</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Scenarios</li>
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
                <a href="/scenarios/new" id="scenario-list-add-btn" class="btn btn-primary"
                   hx-get="/scenarios/new" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/scenarios/new">
                    <i class="feather-plus me-2"></i>
                    <span>Add Scenario</span>
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
<div class="main-content" id="scenario-list-content" data-screen="scenario-list">
    <div class="row">
        <div class="col-lg-12">
            <form id="scenario-list-filters" class="d-flex flex-wrap gap-2 mb-3" action="/scenarios/" method="get">
                <select name="exam_id" id="scenario-list-filter-exam" class="form-select w-auto"
                        hx-get="/scenarios/" hx-target="#scenario-list-results" hx-swap="outerHTML"
                        hx-include="#scenario-list-filters">
                    <option value="">All exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?= e($exam['id']) ?>" <?= (int) $filters['exam_id'] === (int) $exam['id'] ? 'selected' : '' ?>>
                            <?= e($exam['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" id="scenario-list-filter-status" class="form-select w-auto"
                        hx-get="/scenarios/" hx-target="#scenario-list-results" hx-swap="outerHTML"
                        hx-include="#scenario-list-filters">
                    <option value="">All statuses</option>
                    <?php foreach (['draft', 'active', 'retired'] as $statusOption): ?>
                        <option value="<?= e($statusOption) ?>" <?= $filters['status'] === $statusOption ? 'selected' : '' ?>>
                            <?= e(ucfirst($statusOption)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?= view('scenarios/partials/table.php', ['result' => $result, 'filters' => $filters]) ?>
    </div>
</div>
<!-- [ Main Content ] end -->
