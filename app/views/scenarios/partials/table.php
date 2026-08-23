<?php
/** Results region. Vars: $result, $filters. */
$total = $result['total'];
$pages = max(1, (int) ceil($total / SCENARIOS_PAGE_SIZE));
$page  = min(max(1, (int) $filters['page']), $pages);
$qs = static function (int $toPage) use ($filters): string {
    return '/scenarios/?' . http_build_query(array_filter([
        'exam_id' => $filters['exam_id'] ?: null,
        'status'  => $filters['status'] ?: null,
        'page'    => $toPage > 1 ? $toPage : null,
    ]));
};
?>
<div class="col-lg-12" id="scenario-list-results">
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h5 class="card-title">Scenarios <small class="text-muted">(<?= e($total) ?>)</small></h5>
        </div>
        <div class="card-body custom-card-action p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="scenario-list-table">
                    <thead class="thead-light">
                        <tr>
                            <th id="scenario-list-col-title">Scenario</th>
                            <th id="scenario-list-col-exam">Exam</th>
                            <th id="scenario-list-col-status">Status</th>
                            <th id="scenario-list-col-questions">Linked Questions</th>
                            <th id="scenario-list-col-updated">Updated</th>
                            <th class="text-end" id="scenario-list-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result['rows'] === []): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No scenarios yet. Scenario rounds power the CCAR-F exam format.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($result['rows'] as $scenario): ?>
                            <?= view('scenarios/partials/row.php', ['scenario' => $scenario]) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0" id="scenario-list-pagination">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e($qs($i)) ?>"
                                   hx-get="<?= e($qs($i)) ?>" hx-target="#scenario-list-results" hx-swap="outerHTML"><?= e($i) ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
