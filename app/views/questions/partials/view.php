<?php
/**
 * Question detail screen. Vars: $question (with options + stats), $saved (?string action).
 */
$statusColors = ['draft' => 'dark', 'active' => 'success', 'retired' => 'secondary'];
$color = $statusColors[$question['status']] ?? 'secondary';
$id    = (int) $question['id'];
$optionColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
$isDeletable = $question['status'] === 'draft' && (int) $question['times_drawn'] === 0;
?>
<!-- [ page-header ] start -->
<div class="page-header" id="question-view-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Question</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/questions/"
                hx-get="/questions/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/questions/">Questions</a></li>
            <li class="breadcrumb-item">#<?= e($id) ?></li>
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
                <?php if ($question['status'] !== 'active'): ?>
                    <button type="button" id="question-view-activate-btn" class="btn btn-light-brand"
                            hx-post="/questions/status" hx-vals='{"id": <?= $id ?>, "status": "active"}'
                            hx-target="#page-content" hx-swap="innerHTML">
                        <i class="feather-check me-2"></i>
                        <span>Activate</span>
                    </button>
                <?php else: ?>
                    <button type="button" id="question-view-retire-btn" class="btn btn-light-brand"
                            hx-post="/questions/status" hx-vals='{"id": <?= $id ?>, "status": "retired"}'
                            hx-target="#page-content" hx-swap="innerHTML">
                        <i class="feather-archive me-2"></i>
                        <span>Retire</span>
                    </button>
                <?php endif; ?>
                <?php if ($isDeletable): ?>
                    <button type="button" id="question-view-delete-btn" class="btn btn-light-brand text-danger"
                            hx-post="/questions/delete" hx-vals='{"id": <?= $id ?>}'
                            hx-confirm="Delete this draft question? This cannot be undone.">
                        <i class="feather-trash-2 me-2"></i>
                        <span>Delete</span>
                    </button>
                <?php endif; ?>
                <a href="/questions/<?= $id ?>/edit" id="question-view-edit-btn" class="btn btn-primary"
                   hx-get="/questions/<?= $id ?>/edit" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/questions/<?= $id ?>/edit">
                    <i class="feather-edit me-2"></i>
                    <span>Edit</span>
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
<div class="main-content" id="question-view-content" data-screen="question-view" data-entity="questions" data-record-id="<?= $id ?>">
    <div class="row">
        <div class="col-lg-8">
            <?php if (!empty($saved)): ?>
                <?= view('questions/partials/saved.php', ['savedAction' => $saved]) ?>
            <?php endif; ?>
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Question</h5>
                </div>
                <div class="card-body">
                    <p class="fs-14" id="question-view-stem"><?= e($question['stem']) ?></p>
                    <ul class="list-group" id="question-view-options">
                        <?php foreach ($question['options'] as $index => $option): ?>
                            <li class="list-group-item d-flex align-items-center" id="question-view-option-<?= e($option['display_order']) ?>">
                                <span class="wd-10 ht-10 bg-<?= e($optionColors[$index] ?? 'secondary') ?> me-3 d-inline-block rounded-circle"></span>
                                <span class="flex-fill"><?= e($option['option_text']) ?></span>
                                <?php if ($option['is_correct']): ?>
                                    <span class="badge bg-soft-success text-success">Correct</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <h6 class="fs-13 fw-bold mt-4">Explanation</h6>
                    <p class="fs-12 text-muted mb-0" id="question-view-explanation"><?= e($question['explanation']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0" id="question-view-meta">
                        <tbody>
                            <tr><td class="text-muted">Exam</td><td><span class="badge bg-soft-primary text-primary"><?= e($question['exam_code']) ?></span></td></tr>
                            <tr><td class="text-muted">Domain</td><td><?= e($question['domain_name']) ?></td></tr>
                            <tr><td class="text-muted">Difficulty</td><td><?= e(ucfirst($question['difficulty'])) ?></td></tr>
                            <tr><td class="text-muted">Status</td><td><span class="badge bg-soft-<?= e($color) ?> text-<?= e($color) ?>"><?= e(ucfirst($question['status'])) ?></span></td></tr>
                            <tr><td class="text-muted">Author</td><td><?= e($question['author_name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Source</td><td><small class="text-muted"><?= e($question['source'] !== '' ? $question['source'] : '—') ?></small></td></tr>
                            <tr><td class="text-muted">Updated</td><td><?= e(fmt_date($question['updated_at'])) ?></td></tr>
                            <tr><td class="text-muted">Times played</td><td><?= e($question['times_answered']) ?></td></tr>
                            <?php if ((int) $question['times_answered'] > 0): ?>
                                <tr><td class="text-muted">Correct rate</td><td><?= e(round(100 * (int) $question['times_correct'] / max(1, (int) $question['times_answered']))) ?>%</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
