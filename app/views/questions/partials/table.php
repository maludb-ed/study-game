<?php
/** Results region (table + pagination). Vars: $result, $filters. Root id sub-swaps. */
$total = $result['total'];
$pages = max(1, (int) ceil($total / QUESTIONS_PAGE_SIZE));
$page  = min(max(1, (int) $filters['page']), $pages);
$qs = static function (int $toPage) use ($filters): string {
    return '/questions/?' . http_build_query(array_filter([
        'exam_id'   => $filters['exam_id'] ?: null,
        'domain_id' => $filters['domain_id'] ?: null,
        'status'    => $filters['status'] ?: null,
        'q'         => $filters['q'] ?: null,
        'page'      => $toPage > 1 ? $toPage : null,
    ]));
};
?>
<div class="col-lg-12" id="question-list-results">
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h5 class="card-title">Question Bank <small class="text-muted">(<?= e($total) ?>)</small></h5>
        </div>
        <div class="card-body custom-card-action p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="question-list-table">
                    <thead class="thead-light">
                        <tr>
                            <th id="question-list-col-stem">Question</th>
                            <th id="question-list-col-exam">Exam</th>
                            <th id="question-list-col-domain">Domain</th>
                            <th id="question-list-col-difficulty">Difficulty</th>
                            <th id="question-list-col-status">Status</th>
                            <th id="question-list-col-updated">Updated</th>
                            <th class="text-end" id="question-list-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result['rows'] === []): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No questions match. Add one or import a batch.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($result['rows'] as $question): ?>
                            <?= view('questions/partials/row.php', ['question' => $question]) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0" id="question-list-pagination">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e($qs($i)) ?>"
                                   hx-get="<?= e($qs($i)) ?>" hx-target="#question-list-results" hx-swap="outerHTML"><?= e($i) ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
