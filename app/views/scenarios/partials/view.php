<?php
/** Scenario detail. Vars: $scenario (with questions), $saved (?string). */
$statusColors = ['draft' => 'dark', 'active' => 'success', 'retired' => 'secondary'];
$color = $statusColors[$scenario['status']] ?? 'secondary';
$id = (int) $scenario['id'];
$isDeletable = $scenario['status'] === 'draft' && $scenario['questions'] === [];
?>
<!-- [ page-header ] start -->
<div class="page-header" id="scenario-view-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Scenario</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/scenarios/"
                hx-get="/scenarios/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/scenarios/">Scenarios</a></li>
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
                <?php if ($scenario['status'] !== 'active'): ?>
                    <button type="button" id="scenario-view-activate-btn" class="btn btn-light-brand"
                            hx-post="/scenarios/status" hx-vals='{"id": <?= $id ?>, "status": "active"}'
                            hx-target="#page-content" hx-swap="innerHTML">
                        <i class="feather-check me-2"></i>
                        <span>Activate</span>
                    </button>
                <?php else: ?>
                    <button type="button" id="scenario-view-retire-btn" class="btn btn-light-brand"
                            hx-post="/scenarios/status" hx-vals='{"id": <?= $id ?>, "status": "retired"}'
                            hx-target="#page-content" hx-swap="innerHTML">
                        <i class="feather-archive me-2"></i>
                        <span>Retire</span>
                    </button>
                <?php endif; ?>
                <?php if ($isDeletable): ?>
                    <button type="button" id="scenario-view-delete-btn" class="btn btn-light-brand text-danger"
                            hx-post="/scenarios/delete" hx-vals='{"id": <?= $id ?>}'
                            hx-confirm="Delete this draft scenario? This cannot be undone.">
                        <i class="feather-trash-2 me-2"></i>
                        <span>Delete</span>
                    </button>
                <?php endif; ?>
                <a href="/scenarios/<?= $id ?>/edit" id="scenario-view-edit-btn" class="btn btn-primary"
                   hx-get="/scenarios/<?= $id ?>/edit" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/scenarios/<?= $id ?>/edit">
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
<div class="main-content" id="scenario-view-content" data-screen="scenario-view" data-entity="scenarios" data-record-id="<?= $id ?>">
    <div class="row">
        <div class="col-lg-8">
            <?php if (!empty($saved)): ?>
                <?= view('scenarios/partials/saved.php', ['savedAction' => $saved]) ?>
            <?php endif; ?>
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title"><?= e($scenario['title']) ?></h5>
                </div>
                <div class="card-body">
                    <p class="fs-13" id="scenario-view-body"><?= nl2br(e($scenario['body'])) ?></p>
                    <h6 class="fs-13 fw-bold mt-4">Linked Questions <small class="text-muted">(play order)</small></h6>
                    <?php if ($scenario['questions'] === []): ?>
                        <p class="fs-12 text-muted mb-0" id="scenario-view-no-questions">
                            None yet — attach questions from the question form.
                        </p>
                    <?php else: ?>
                        <ul class="list-group" id="scenario-view-questions">
                            <?php foreach ($scenario['questions'] as $question): ?>
                                <?php $qColor = ['draft' => 'dark', 'active' => 'success', 'retired' => 'secondary'][$question['status']] ?? 'secondary'; ?>
                                <li class="list-group-item d-flex align-items-center" id="scenario-view-question-<?= e($question['id']) ?>">
                                    <span class="wd-10 ht-10 bg-<?= e($qColor) ?> me-3 d-inline-block rounded-circle"></span>
                                    <a class="flex-fill" href="/questions/<?= e($question['id']) ?>"
                                       hx-get="/questions/<?= e($question['id']) ?>" hx-target="#page-content" hx-swap="innerHTML"
                                       hx-push-url="/questions/<?= e($question['id']) ?>">
                                        <?= e(mb_strlen($question['stem']) > 90 ? mb_substr($question['stem'], 0, 90) . '…' : $question['stem']) ?>
                                    </a>
                                    <small class="text-muted ms-2"><?= e($question['domain_name']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0" id="scenario-view-meta">
                        <tbody>
                            <tr><td class="text-muted">Exam</td><td><span class="badge bg-soft-primary text-primary"><?= e($scenario['exam_code']) ?></span></td></tr>
                            <tr><td class="text-muted">Status</td><td><span class="badge bg-soft-<?= e($color) ?> text-<?= e($color) ?>"><?= e(ucfirst($scenario['status'])) ?></span></td></tr>
                            <tr><td class="text-muted">Author</td><td><?= e($scenario['author_name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Questions</td><td><?= e(count($scenario['questions'])) ?></td></tr>
                            <tr><td class="text-muted">Updated</td><td><?= e(fmt_date($scenario['updated_at'])) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
