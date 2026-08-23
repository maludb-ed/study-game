<?php
/** Add/edit screen. Vars: $scenario (array; empty id = add), $exams, $errors. */
$isEdit = !empty($scenario['id']);
$title  = $isEdit ? 'Edit Scenario' : 'Add Scenario';
$cancelUrl = $isEdit ? '/scenarios/' . (int) $scenario['id'] : '/scenarios/';
?>
<!-- [ page-header ] start -->
<div class="page-header" id="scenario-form-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10"><?= e($title) ?></h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/scenarios/"
                hx-get="/scenarios/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/scenarios/">Scenarios</a></li>
            <li class="breadcrumb-item"><?= e($title) ?></li>
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
                <a href="<?= e($cancelUrl) ?>" id="scenario-form-cancel-btn" class="btn btn-light-brand"
                   hx-get="<?= e($cancelUrl) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($cancelUrl) ?>">
                    <span>Cancel</span>
                </a>
                <button type="submit" form="scenario-form" id="scenario-form-save-btn" class="btn btn-primary">
                    <i class="feather-save me-2"></i>
                    <span>Save</span>
                </button>
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
<div class="main-content" id="scenario-form-content" data-screen="<?= $isEdit ? 'scenario-edit' : 'scenario-add' ?>">
    <div class="row">
        <div class="col-lg-12" id="scenario-form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fs-12" id="scenario-form-errors">
                    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form id="scenario-form" action="/scenarios/save" method="post"
                  hx-post="/scenarios/save" hx-target="#page-content" hx-swap="innerHTML">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($scenario['id']) ?>"><?php endif; ?>
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="mb-4 row" id="scenario-form-field-exam-row">
                            <label class="col-lg-4 col-form-label" for="scenario-form-field-exam">Exam</label>
                            <div class="col-lg-8">
                                <select name="exam_id" id="scenario-form-field-exam" class="form-select" required>
                                    <option value="">Choose an exam…</option>
                                    <?php foreach ($exams as $exam): ?>
                                        <option value="<?= e($exam['id']) ?>" <?= (int) ($scenario['exam_id'] ?? 0) === (int) $exam['id'] ? 'selected' : '' ?>>
                                            <?= e($exam['code']) ?> — <?= e($exam['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4 row" id="scenario-form-field-title-row">
                            <label class="col-lg-4 col-form-label" for="scenario-form-field-title">Title</label>
                            <div class="col-lg-8">
                                <input type="text" name="title" id="scenario-form-field-title" class="form-control"
                                       minlength="5" maxlength="120" required value="<?= e($scenario['title'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-4 row" id="scenario-form-field-body-row">
                            <label class="col-lg-4 col-form-label" for="scenario-form-field-body">Scenario
                                <small class="text-muted d-block">Shown full-screen before its questions</small></label>
                            <div class="col-lg-8">
                                <textarea name="body" id="scenario-form-field-body" class="form-control" rows="8"
                                          minlength="50" maxlength="4000" required><?= e($scenario['body'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="mb-2 row" id="scenario-form-field-status-row">
                            <label class="col-lg-4 col-form-label" for="scenario-form-field-status">Status</label>
                            <div class="col-lg-8">
                                <select name="status" id="scenario-form-field-status" class="form-select" required>
                                    <?php foreach (['draft', 'active', 'retired'] as $statusOption): ?>
                                        <option value="<?= e($statusOption) ?>" <?= ($scenario['status'] ?? 'draft') === $statusOption ? 'selected' : '' ?>>
                                            <?= e(ucfirst($statusOption)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <p class="fs-12 text-muted mb-0" id="scenario-form-attach-hint">
                            Attach questions from the question form — each question's Scenario select lists this exam's scenarios.
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
