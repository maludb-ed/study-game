<?php /* Exam detail: blueprint coverage. Vars: $exam, $coverage (rows with target/short). */ ?>
<!-- [ page-header ] start -->
<div class="page-header" id="exam-view-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10"><?= e($exam['code']) ?></h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/exams/"
                hx-get="/exams/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/exams/">Exams</a></li>
            <li class="breadcrumb-item"><?= e($exam['code']) ?></li>
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
                <a href="/questions/?exam_id=<?= e($exam['id']) ?>" id="exam-view-questions-btn" class="btn btn-primary"
                   hx-get="/questions/?exam_id=<?= e($exam['id']) ?>" hx-target="#page-content" hx-swap="innerHTML"
                   hx-push-url="/questions/?exam_id=<?= e($exam['id']) ?>">
                    <i class="feather-help-circle me-2"></i>
                    <span>Browse Questions</span>
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
<div class="main-content" id="exam-view-content" data-screen="exam-view" data-entity="exams" data-record-id="<?= e($exam['id']) ?>">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title"><?= e($exam['name']) ?></h5>
                </div>
                <div class="card-body custom-card-action p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="exam-view-coverage-table">
                            <thead class="thead-light">
                                <tr>
                                    <th id="exam-view-col-domain">Domain</th>
                                    <th id="exam-view-col-weight">Blueprint Weight</th>
                                    <th id="exam-view-col-active">Active Questions</th>
                                    <th id="exam-view-col-draft">Drafts</th>
                                    <th id="exam-view-col-target">Target (10-question game)</th>
                                    <th class="text-end" id="exam-view-col-state">Bank</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coverage as $domain): ?>
                                    <tr id="exam-domain-row-<?= e($domain['id']) ?>">
                                        <td>
                                            <span class="wd-10 ht-10 bg-<?= $domain['short'] ? 'danger' : 'success' ?> me-2 d-inline-block rounded-circle"></span>
                                            <span><?= e($domain['name']) ?></span>
                                        </td>
                                        <td><?= e($domain['weight_pct']) ?>%</td>
                                        <td><?= e($domain['active_questions']) ?></td>
                                        <td><small class="text-muted"><?= e($domain['draft_questions']) ?></small></td>
                                        <td><?= e($domain['target']) ?></td>
                                        <td class="text-end">
                                            <?php if ($domain['short']): ?>
                                                <span class="badge bg-soft-danger text-danger">Short</span>
                                            <?php else: ?>
                                                <span class="badge bg-soft-success text-success">Covered</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
