<?php
/**
 * Per-domain group performance table (analytics-group). Vars: $domainPerformance —
 * rows from group_domain_performance(): domain_id, domain_name, weight_pct,
 * answered_count, accuracy_pct (nullable), trend ('up'/'down'/'flat'/null),
 * weakest_member (nullable). Group figures use ALL answers, claimed or not.
 */
?>
<div class="col-lg-12" id="analytics-group-domain-table">
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h5 class="card-title">Domain Performance</h5>
        </div>
        <div class="card-body custom-card-action p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="analytics-group-domain-table-inner">
                    <thead class="thead-light">
                        <tr>
                            <th id="analytics-group-domain-col-name">Domain</th>
                            <th id="analytics-group-domain-col-weight">Weight</th>
                            <th id="analytics-group-domain-col-accuracy">Group Accuracy</th>
                            <th id="analytics-group-domain-col-answers">Answers</th>
                            <th id="analytics-group-domain-col-trend">Trend</th>
                            <th id="analytics-group-domain-col-weakest">Weakest Member</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($domainPerformance === []): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No domains for this exam.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($domainPerformance as $row): ?>
                            <tr id="analytics-domain-row-<?= e($row['domain_id']) ?>">
                                <td><?= e($row['domain_name']) ?></td>
                                <td><?= e($row['weight_pct']) ?>%</td>
                                <td>
                                    <?php if ($row['accuracy_pct'] === null): ?>
                                        <span class="text-muted">—</span>
                                    <?php else: ?>
                                        <?= e($row['accuracy_pct']) ?>%
                                    <?php endif; ?>
                                </td>
                                <td><?= e($row['answered_count']) ?></td>
                                <td>
                                    <?php if ($row['trend'] === 'up'): ?>
                                        <i class="feather-trending-up text-success" title="Improving vs. the prior period"></i>
                                    <?php elseif ($row['trend'] === 'down'): ?>
                                        <i class="feather-trending-down text-danger" title="Slipping vs. the prior period"></i>
                                    <?php elseif ($row['trend'] === 'flat'): ?>
                                        <i class="feather-minus text-muted" title="Little change vs. the prior period"></i>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['weakest_member'] === null): ?>
                                        <span class="text-muted">—</span>
                                    <?php else: ?>
                                        <small class="text-muted"><?= e($row['weakest_member']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="fs-12 text-muted px-3 py-2 mb-0">
                Accuracy and answer counts include every submitted answer, claimed or not.
                The weakest-member hint uses claimed answers only.
            </p>
        </div>
    </div>
</div>
