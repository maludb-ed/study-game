<?php /* The four certifications with bank counts. Vars: $exams. */ ?>
<div class="col-lg-12" id="exam-list-results">
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h5 class="card-title">Anthropic Certification Exams</h5>
        </div>
        <div class="card-body custom-card-action p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="exam-list-table">
                    <thead class="thead-light">
                        <tr>
                            <th id="exam-list-col-exam">Exam</th>
                            <th id="exam-list-col-audience">Audience</th>
                            <th id="exam-list-col-format">Format</th>
                            <th id="exam-list-col-bank">Question Bank</th>
                            <th class="text-end" id="exam-list-col-actions">Coverage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <?php $url = '/exams/' . (int) $exam['id']; ?>
                            <tr id="exam-row-<?= e($exam['id']) ?>">
                                <td>
                                    <a href="<?= e($url) ?>"
                                       hx-get="<?= e($url) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($url) ?>">
                                        <span class="wd-10 ht-10 bg-<?= (int) $exam['active_questions'] > 0 ? 'success' : 'secondary' ?> me-2 d-inline-block rounded-circle"></span>
                                        <span><?= e($exam['code']) ?></span>
                                        <small class="text-muted d-block ms-4"><?= e($exam['name']) ?></small>
                                    </a>
                                </td>
                                <td><small class="text-muted"><?= e($exam['audience']) ?></small></td>
                                <td><?= e($exam['official_question_count']) ?> questions
                                    <small class="text-muted">(<?= e($exam['duration_minutes']) ?> min, pass <?= e($exam['passing_score']) ?>)</small></td>
                                <td><?= e($exam['active_questions']) ?> active <small class="text-muted">/ <?= e($exam['total_questions']) ?> total</small></td>
                                <td class="text-end">
                                    <a href="<?= e($url) ?>" id="exam-row-<?= e($exam['id']) ?>-coverage-btn" class="avatar-text avatar-md"
                                       hx-get="<?= e($url) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($url) ?>">
                                        <i class="feather-bar-chart-2"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
