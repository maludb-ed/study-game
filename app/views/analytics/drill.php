<?php
/**
 * Drill list screen (most-missed questions, minimum 3 plays). Vars: $result
 * ['rows','total'], $filters ['exam_id','domain_id','page'], $exams, $domains,
 * $resultsOnly (bool). This one file serves both render modes (build spec amendment 8
 * lists no separate results partial for the drill screen): $resultsOnly = true renders
 * just the #drill-list-table region for the HX-Target sub-swap (domain filter change /
 * pagination); false renders the full page-header + main-content, matching the
 * questions exemplar's filter behaviour (exam change repopulates the domain filter and
 * so re-renders the whole screen; other filters swap only the results region).
 */
$resultsOnly = $resultsOnly ?? false;
$total = $result['total'];
$pages = max(1, (int) ceil($total / ANALYTICS_DRILL_PAGE_SIZE));
$page  = min(max(1, (int) $filters['page']), $pages);
$qs = static function (int $toPage) use ($filters): string {
    return '/analytics/drill?' . http_build_query(array_filter([
        'exam_id'   => $filters['exam_id'] ?: null,
        'domain_id' => $filters['domain_id'] ?: null,
        'page'      => $toPage > 1 ? $toPage : null,
    ]));
};
if (!$resultsOnly):
?>
<!-- [ page-header ] start -->
<div class="page-header" id="drill-list-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Drill List</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Drill List</li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="drill-list-content" data-screen="drill-list">
    <div class="row">
        <div class="col-lg-12">
            <form id="drill-list-filters" class="d-flex flex-wrap gap-2 mb-3" action="/analytics/drill" method="get">
                <select name="exam_id" id="drill-list-filter-exam" class="form-select w-auto"
                        hx-get="/analytics/drill" hx-target="#page-content" hx-swap="innerHTML">
                    <option value="">All exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?= e($exam['id']) ?>" <?= (int) $filters['exam_id'] === (int) $exam['id'] ? 'selected' : '' ?>>
                            <?= e($exam['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="domain_id" id="drill-list-filter-domain" class="form-select w-auto"
                        hx-get="/analytics/drill" hx-target="#drill-list-table" hx-swap="outerHTML"
                        hx-include="#drill-list-filters" <?= $domains === [] ? 'disabled' : '' ?>>
                    <option value="">All domains</option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?= e($domain['id']) ?>" <?= (int) $filters['domain_id'] === (int) $domain['id'] ? 'selected' : '' ?>>
                            <?= e($domain['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
<?php endif; ?>
        <div class="col-lg-12" id="drill-list-table">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Most Missed <small class="text-muted">(<?= e($total) ?>)</small></h5>
                </div>
                <div class="card-body custom-card-action p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="drill-list-table-inner">
                            <thead class="thead-light">
                                <tr>
                                    <th id="drill-list-col-stem">Question</th>
                                    <th id="drill-list-col-domain">Domain</th>
                                    <th class="text-end" id="drill-list-col-plays">Plays</th>
                                    <th class="text-end" id="drill-list-col-miss">Miss Rate</th>
                                    <th class="text-end" id="drill-list-col-avg">Avg Response</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result['rows'] === []): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Nothing to drill yet — questions need at least 3 plays to show up here.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($result['rows'] as $row): ?>
                                    <?php
                                        $questionUrl = '/questions/' . (int) $row['question_id'];
                                        $stem = mb_strlen($row['stem']) > 80 ? mb_substr($row['stem'], 0, 80) . '…' : $row['stem'];
                                    ?>
                                    <tr id="drill-row-<?= e($row['question_id']) ?>">
                                        <td>
                                            <a href="<?= e($questionUrl) ?>"
                                               hx-get="<?= e($questionUrl) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($questionUrl) ?>">
                                                <?= e($stem) ?>
                                            </a>
                                        </td>
                                        <td><small class="text-muted"><?= e($row['domain_name']) ?></small></td>
                                        <td class="text-end"><?= e($row['plays']) ?></td>
                                        <td class="text-end"><?= e($row['miss_rate_pct']) ?>%</td>
                                        <td class="text-end">
                                            <?= $row['avg_response_s'] === null ? '—' : e(number_format((float) $row['avg_response_s'], 1)) . 's' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($pages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center mb-0" id="drill-list-pagination">
                                <?php for ($i = 1; $i <= $pages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= e($qs($i)) ?>"
                                           hx-get="<?= e($qs($i)) ?>" hx-target="#drill-list-table" hx-swap="outerHTML"><?= e($i) ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
<?php if (!$resultsOnly): ?>
    </div>
</div>
<!-- [ Main Content ] end -->
<?php endif; ?>
