<?php /* Validation/import result region. Vars: $report (?array of ['index','errors'[]]), $imported (?int) */ ?>
<div class="card stretch stretch-full" id="question-import-report">
    <div class="card-header">
        <h5 class="card-title">Result</h5>
    </div>
    <div class="card-body">
        <?php if ($imported !== null): ?>
            <div class="alert alert-success fs-12 mb-0" id="question-import-success">
                <i class="feather-check-circle me-1"></i>
                <?= e($imported) ?> question<?= $imported === 1 ? '' : 's' ?> imported as drafts.
                <a href="/questions/?status=draft" hx-get="/questions/?status=draft" hx-target="#page-content"
                   hx-swap="innerHTML" hx-push-url="/questions/?status=draft">Review them</a>.
            </div>
        <?php elseif ($report === null): ?>
            <p class="fs-12 text-muted mb-0">Validate a batch to see the report here.</p>
        <?php elseif ($report === []): ?>
            <div class="alert alert-success fs-12 mb-0" id="question-import-valid">
                <i class="feather-check-circle me-1"></i>The batch is valid — ready to import.
            </div>
        <?php else: ?>
            <div class="alert alert-danger fs-12" id="question-import-errors-summary">
                <?= e(count($report)) ?> item<?= count($report) === 1 ? '' : 's' ?> failed validation. Nothing was imported.
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="question-import-errors-table">
                    <thead class="thead-light"><tr><th>#</th><th>Problems</th></tr></thead>
                    <tbody>
                        <?php foreach ($report as $item): ?>
                            <tr>
                                <td><?= e($item['index']) ?></td>
                                <td><small class="text-muted"><?= e(implode(' ', $item['errors'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
