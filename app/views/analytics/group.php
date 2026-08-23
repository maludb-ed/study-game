<?php
/**
 * Group readiness screen. Vars: $exams, $examId, $domainPerformance, $tiles, $members
 * (each augmented by the controller with score/coverage_pct/band_status/band_label from
 * member_readiness()). Swaps into #page-content; exam tab clicks always re-render the
 * whole screen (they need to refresh every region, unlike a plain filter sub-swap).
 */
?>
<!-- [ page-header ] start -->
<div class="page-header" id="analytics-group-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Group Readiness</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Analytics</li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="analytics-group-content" data-screen="analytics-group">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="analytics-group-tabs">
                        <?php foreach ($exams as $exam): ?>
                            <?php $tabUrl = '/analytics/?exam_id=' . (int) $exam['id']; ?>
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
                    <div class="row" id="analytics-group-tiles">
                        <?php
                        $tileCards = [
                            ['id' => 'analytics-group-tile-games',   'icon' => 'feather-play',         'label' => 'Games Played',        'value' => $tiles['games_played']],
                            ['id' => 'analytics-group-tile-answers', 'icon' => 'feather-check-circle', 'label' => 'Questions Answered',  'value' => $tiles['questions_answered']],
                            ['id' => 'analytics-group-tile-score',   'icon' => 'feather-award',        'label' => 'Group Weighted Score', 'value' => $tiles['weighted_score'] === null ? '—' : $tiles['weighted_score']],
                            ['id' => 'analytics-group-tile-ready',   'icon' => 'feather-users',        'label' => 'Members ≥ 720',       'value' => $tiles['members_ready']],
                        ];
                        ?>
                        <?php foreach ($tileCards as $card): ?>
                            <div class="col-xxl-3 col-md-6" id="<?= e($card['id']) ?>">
                                <div class="card stretch stretch-full border">
                                    <div class="card-body">
                                        <div class="d-flex gap-3 align-items-center">
                                            <div class="avatar-text avatar-lg bg-gray-200">
                                                <i class="<?= e($card['icon']) ?>"></i>
                                            </div>
                                            <div>
                                                <div class="fs-4 fw-bold text-dark"><?= e($card['value']) ?></div>
                                                <h3 class="fs-13 fw-semibold text-truncate-1-line"><?= e($card['label']) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?= view('analytics/partials/domain-table.php', ['domainPerformance' => $domainPerformance]) ?>

        <div class="col-lg-12" id="analytics-group-members-table">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Members</h5>
                </div>
                <div class="card-body custom-card-action p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="analytics-group-members-table-inner">
                            <thead class="thead-light">
                                <tr>
                                    <th id="analytics-group-members-col-name">Name</th>
                                    <th class="text-end" id="analytics-group-members-col-games">Games</th>
                                    <th id="analytics-group-members-col-score">Weighted Score</th>
                                    <th id="analytics-group-members-col-last">Last Played</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($members === []): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No members have joined a game for this exam yet.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($members as $member): ?>
                                    <?php $memberUrl = '/analytics/members/' . (int) $member['user_id']; ?>
                                    <tr id="analytics-member-row-<?= e($member['user_id']) ?>">
                                        <td>
                                            <a href="<?= e($memberUrl) ?>"
                                               hx-get="<?= e($memberUrl) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($memberUrl) ?>">
                                                <?= e($member['display_name']) ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?= e($member['games_count']) ?></td>
                                        <td>
                                            <span class="wd-10 ht-10 bg-<?= e($member['band_status']) ?> me-2 d-inline-block rounded-circle"></span>
                                            <span class="badge bg-soft-<?= e($member['band_status']) ?> text-<?= e($member['band_status']) ?>"><?= e($member['score']) ?></span>
                                        </td>
                                        <td><?= e(fmt_date($member['last_played'])) ?></td>
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
