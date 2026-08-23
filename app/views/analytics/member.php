<?php
/**
 * Member readiness screen. Vars: $member (users row: id, display_name, email), $examId,
 * $exams, $readiness (member_readiness() result for the selected exam), $examsPlayedCount,
 * $claimedAnswerCount, $unclaimedCount (global unclaimed game_players — 0 hides the note).
 */
?>
<!-- [ page-header ] start -->
<div class="page-header" id="analytics-member-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10"><?= e($member['display_name']) ?></h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/analytics/"
                hx-get="/analytics/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/analytics/">Group Readiness</a></li>
            <li class="breadcrumb-item"><?= e($member['display_name']) ?></li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="analytics-member-content" data-screen="analytics-member" data-entity="users" data-record-id="<?= e($member['id']) ?>">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <p class="text-muted fs-12 mb-1">Member</p>
                            <p class="fw-bold mb-0"><?= e($member['display_name']) ?></p>
                        </div>
                        <div class="col-sm-4">
                            <p class="text-muted fs-12 mb-1">Exams Played</p>
                            <p class="fw-bold mb-0"><?= e($examsPlayedCount) ?></p>
                        </div>
                        <div class="col-sm-4">
                            <p class="text-muted fs-12 mb-1">Claimed Answers</p>
                            <p class="fw-bold mb-0"><?= e($claimedAnswerCount) ?></p>
                        </div>
                    </div>
                    <?php if ($unclaimedCount > 0): ?>
                        <p class="fs-12 text-muted mt-3 mb-0">
                            <i class="feather-info me-1"></i>
                            <?= e($unclaimedCount) ?> game<?= $unclaimedCount === 1 ? '' : 's' ?> played unclaimed — claim on next join to count them.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="analytics-member-tabs">
                        <?php foreach ($exams as $exam): ?>
                            <?php $tabUrl = '/analytics/members/' . (int) $member['id'] . '?exam_id=' . (int) $exam['id']; ?>
                            <li class="nav-item">
                                <a class="nav-link <?= (int) $exam['id'] === $examId ? 'active' : '' ?>"
                                   href="<?= e($tabUrl) ?>"
                                   hx-get="<?= e($tabUrl) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($tabUrl) ?>">
                                    <?= e($exam['code']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?= view('analytics/partials/readiness-banner.php', ['readiness' => $readiness]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12" id="analytics-member-grid">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Domain Readiness</h5>
                </div>
                <div class="card-body custom-card-action p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="analytics-member-grid-inner">
                            <thead class="thead-light">
                                <tr>
                                    <th id="analytics-member-grid-col-domain">Domain</th>
                                    <th id="analytics-member-grid-col-weight">Weight</th>
                                    <th id="analytics-member-grid-col-accuracy">Accuracy</th>
                                    <th id="analytics-member-grid-col-seen">Seen / Active</th>
                                    <th id="analytics-member-grid-col-contribution">Weighted Contribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($readiness['domains'] === []): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No domains for this exam.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($readiness['domains'] as $row): ?>
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
                                        <td><?= e($row['seen_question_count']) ?> / <?= e($row['active_question_count']) ?></td>
                                        <td><?= e($row['weighted_contribution']) ?></td>
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
