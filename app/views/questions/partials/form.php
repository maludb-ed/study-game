<?php
/**
 * Add/edit screen (full-container form, Save/Cancel in the page header).
 * Vars: $question (array; empty id = add), $options (4 slots), $correctIndex (1-based),
 *       $exams, $domains, $errors (array).
 */
$isEdit = !empty($question['id']);
$title  = $isEdit ? 'Edit Question' : 'Add Question';
$formUrl = '/questions/save';
$cancelUrl = $isEdit ? '/questions/' . (int) $question['id'] : '/questions/';
?>
<!-- [ page-header ] start -->
<div class="page-header" id="question-form-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10"><?= e($title) ?></h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/questions/"
                hx-get="/questions/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/questions/">Questions</a></li>
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
                <a href="<?= e($cancelUrl) ?>" id="question-form-cancel-btn" class="btn btn-light-brand"
                   hx-get="<?= e($cancelUrl) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($cancelUrl) ?>">
                    <span>Cancel</span>
                </a>
                <button type="submit" form="question-form" id="question-form-save-btn" class="btn btn-primary">
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
<div class="main-content" id="question-form-content" data-screen="<?= $isEdit ? 'question-edit' : 'question-add' ?>">
    <div class="row">
        <div class="col-lg-12" id="question-form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fs-12" id="question-form-errors">
                    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form id="question-form" action="<?= e($formUrl) ?>" method="post"
                  hx-post="<?= e($formUrl) ?>" hx-target="#page-content" hx-swap="innerHTML">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($question['id']) ?>"><?php endif; ?>
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="mb-4 row" id="question-form-field-exam-row">
                            <label class="col-lg-4 col-form-label" id="question-form-field-exam-label" for="question-form-field-exam">Exam</label>
                            <div class="col-lg-8">
                                <select name="exam_id" id="question-form-field-exam" class="form-select" required
                                        hx-get="/questions/dependent-fields" hx-target="#question-form-field-dependent" hx-swap="outerHTML">
                                    <option value="">Choose an exam…</option>
                                    <?php foreach ($exams as $exam): ?>
                                        <option value="<?= e($exam['id']) ?>" <?= (int) ($question['exam_id'] ?? 0) === (int) $exam['id'] ? 'selected' : '' ?>>
                                            <?= e($exam['code']) ?> — <?= e($exam['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?= view('questions/partials/dependent-fields.php', [
                            'domains'          => $domains,
                            'scenarios'        => $scenarios,
                            'domainSelected'   => (int) ($question['domain_id'] ?? 0),
                            'scenarioSelected' => (int) ($question['scenario_id'] ?? 0),
                        ]) ?>
                        <div class="mb-4 row" id="question-form-field-stem-row">
                            <label class="col-lg-4 col-form-label" id="question-form-field-stem-label" for="question-form-field-stem">Question</label>
                            <div class="col-lg-8">
                                <textarea name="stem" id="question-form-field-stem" class="form-control" rows="3"
                                          minlength="10" maxlength="1000" required><?= e($question['stem'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <?php $optionColors = ['danger', 'primary', 'warning', 'success']; ?>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="mb-3 row" id="question-form-field-option-<?= $i ?>-row">
                                <label class="col-lg-4 col-form-label" for="question-form-field-option-<?= $i ?>">
                                    Option <?= $i ?><?= $i > 2 ? ' <small class="text-muted">(optional)</small>' : '' ?>
                                </label>
                                <div class="col-lg-8">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <span class="wd-10 ht-10 bg-<?= e($optionColors[$i - 1]) ?> d-inline-block rounded-circle"></span>
                                        </span>
                                        <input type="text" name="option_<?= $i ?>" id="question-form-field-option-<?= $i ?>"
                                               class="form-control" maxlength="300"
                                               value="<?= e($options[$i] ?? '') ?>" <?= $i <= 2 ? 'required' : '' ?>>
                                        <span class="input-group-text">
                                            <input class="form-check-input mt-0" type="radio" name="correct" value="<?= $i ?>"
                                                   id="question-form-field-correct-<?= $i ?>"
                                                   <?= $correctIndex === $i ? 'checked' : '' ?> required>
                                            <label class="fs-11 text-muted ms-1" for="question-form-field-correct-<?= $i ?>">correct</label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                        <div class="mb-4 row" id="question-form-field-explanation-row">
                            <label class="col-lg-4 col-form-label" id="question-form-field-explanation-label" for="question-form-field-explanation">Explanation <small class="text-muted">(shown at reveal)</small></label>
                            <div class="col-lg-8">
                                <textarea name="explanation" id="question-form-field-explanation" class="form-control" rows="3"
                                          minlength="10" maxlength="2000" required><?= e($question['explanation'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="mb-4 row" id="question-form-field-difficulty-row">
                            <label class="col-lg-4 col-form-label" for="question-form-field-difficulty">Difficulty</label>
                            <div class="col-lg-8">
                                <select name="difficulty" id="question-form-field-difficulty" class="form-select" required>
                                    <?php foreach (['easy', 'medium', 'hard'] as $difficultyOption): ?>
                                        <option value="<?= e($difficultyOption) ?>" <?= ($question['difficulty'] ?? 'medium') === $difficultyOption ? 'selected' : '' ?>>
                                            <?= e(ucfirst($difficultyOption)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4 row" id="question-form-field-status-row">
                            <label class="col-lg-4 col-form-label" for="question-form-field-status">Status</label>
                            <div class="col-lg-8">
                                <select name="status" id="question-form-field-status" class="form-select" required>
                                    <?php foreach (['draft', 'active', 'retired'] as $statusOption): ?>
                                        <option value="<?= e($statusOption) ?>" <?= ($question['status'] ?? 'draft') === $statusOption ? 'selected' : '' ?>>
                                            <?= e(ucfirst($statusOption)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2 row" id="question-form-field-source-row">
                            <label class="col-lg-4 col-form-label" for="question-form-field-source">Source <small class="text-muted">(optional)</small></label>
                            <div class="col-lg-8">
                                <input type="text" name="source" id="question-form-field-source" class="form-control" maxlength="200"
                                       value="<?= e($question['source'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
