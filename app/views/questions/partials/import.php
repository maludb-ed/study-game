<?php /* Import screen. Vars: $json (string), $report (?array from import-report), $imported (?int) */ ?>
<!-- [ page-header ] start -->
<div class="page-header" id="question-import-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Import Questions</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/questions/"
                hx-get="/questions/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/questions/">Questions</a></li>
            <li class="breadcrumb-item">Import</li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="question-import-content" data-screen="question-import">
    <div class="row">
        <div class="col-lg-8">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Paste a JSON batch</h5>
                </div>
                <div class="card-body">
                    <p class="fs-12 text-muted">Format: <code>[{"exam_code", "domain", "stem", "options": [{"text", "correct", "rationale?"}], "explanation", "difficulty?", "source?"}]</code>. 2–6 options; mark <code>"correct": true</code> on one <em>or more</em> (multi-correct is scored all-or-nothing). Optional per-option <code>rationale</code> (why it’s right/wrong, shown in practice). Imports land as <span class="badge bg-soft-dark text-dark">Draft</span>.</p>
                    <form id="question-import-form" action="/questions/import" method="post"
                          hx-post="/questions/import" hx-target="#question-import-report" hx-swap="outerHTML">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="mb-3">
                            <textarea name="json" id="question-import-field-json" class="form-control font-monospace fs-11" rows="14"
                                      placeholder='[{"exam_code": "CCAO-F", "domain": "Prompting and Task Execution", …}]'><?= e($json) ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="do" value="validate" id="question-import-validate-btn" class="btn btn-light-brand">
                                <i class="feather-check-square me-2"></i>Validate
                            </button>
                            <button type="submit" name="do" value="import" id="question-import-run-btn" class="btn btn-primary">
                                <i class="feather-upload me-2"></i>Validate &amp; Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <?= view('questions/partials/import-report.php', ['report' => $report, 'imported' => $imported]) ?>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
