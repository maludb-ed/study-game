<?php
/**
 * Questions list screen. Vars: $result ['rows','total'], $filters
 * ['exam_id','domain_id','status','q','sort','page'], $exams, $domains.
 * Swaps into #page-content; the results region sub-swaps via #question-list-results.
 */
?>
<!-- [ page-header ] start -->
<div class="page-header" id="question-list-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Questions</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Questions</li>
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
                <div class="input-group" id="question-list-search-group">
                    <span class="input-group-text"><i class="feather-search"></i></span>
                    <input type="search" name="q" id="question-list-search" class="form-control" placeholder="Search questions…"
                           value="<?= e($filters['q']) ?>"
                           hx-get="/questions/" hx-target="#question-list-results" hx-swap="outerHTML"
                           hx-include="#question-list-filters" hx-trigger="keyup changed delay:400ms, search">
                </div>
                <a href="/questions/import" id="question-list-import-btn" class="btn btn-light-brand"
                   hx-get="/questions/import" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/questions/import">
                    <i class="feather-upload me-2"></i>
                    <span>Import</span>
                </a>
                <a href="/questions/new" id="question-list-add-btn" class="btn btn-primary"
                   hx-get="/questions/new" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/questions/new">
                    <i class="feather-plus me-2"></i>
                    <span>Add Question</span>
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
<div class="main-content" id="question-list-content" data-screen="question-list">
    <div class="row">
        <div class="col-lg-12">
            <form id="question-list-filters" class="d-flex flex-wrap gap-2 mb-3" action="/questions/" method="get">
                <select name="exam_id" id="question-list-filter-exam" class="form-select w-auto"
                        hx-get="/questions/" hx-target="#page-content" hx-swap="innerHTML">
                    <option value="">All exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?= e($exam['id']) ?>" <?= (int) $filters['exam_id'] === (int) $exam['id'] ? 'selected' : '' ?>>
                            <?= e($exam['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="domain_id" id="question-list-filter-domain" class="form-select w-auto"
                        hx-get="/questions/" hx-target="#question-list-results" hx-swap="outerHTML"
                        hx-include="#question-list-filters, #question-list-search" <?= $domains === [] ? 'disabled' : '' ?>>
                    <option value="">All domains</option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?= e($domain['id']) ?>" <?= (int) $filters['domain_id'] === (int) $domain['id'] ? 'selected' : '' ?>>
                            <?= e($domain['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" id="question-list-filter-status" class="form-select w-auto"
                        hx-get="/questions/" hx-target="#question-list-results" hx-swap="outerHTML"
                        hx-include="#question-list-filters, #question-list-search">
                    <option value="">All statuses</option>
                    <?php foreach (['draft', 'active', 'retired'] as $statusOption): ?>
                        <option value="<?= e($statusOption) ?>" <?= $filters['status'] === $statusOption ? 'selected' : '' ?>>
                            <?= e(ucfirst($statusOption)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?= view('questions/partials/table.php', ['result' => $result, 'filters' => $filters, 'isAdmin' => $isAdmin]) ?>
    </div>
</div>
<!-- [ Main Content ] end -->
