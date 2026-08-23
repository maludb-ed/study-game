<?php /* Vars: $stats (array), $activity (array). Swapped into #page-content. */ ?>
<!-- [ page-header ] start -->
<div class="page-header" id="dashboard-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Dashboard</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Dashboard</li>
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
                <a href="/games/new" id="dashboard-new-game-btn" class="btn btn-primary"
                   hx-get="/games/new" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/games/new">
                    <i class="feather-play me-2"></i>
                    <span>New Game</span>
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
<div class="main-content" id="dashboard-content" data-screen="dashboard">
    <div class="row">
        <?php
        $cards = [
            ['id' => 'dashboard-stat-members',   'icon' => 'feather-users',       'label' => 'Study Group Members', 'value' => $stats['members']],
            ['id' => 'dashboard-stat-questions', 'icon' => 'feather-help-circle', 'label' => 'Active Questions',    'value' => $stats['active_questions']],
            ['id' => 'dashboard-stat-games',     'icon' => 'feather-play',        'label' => 'Games Played',        'value' => $stats['games_played']],
            ['id' => 'dashboard-stat-answers',   'icon' => 'feather-check-circle','label' => 'Answers Recorded',    'value' => $stats['answers_recorded']],
        ];
        ?>
        <?php foreach ($cards as $card): ?>
            <div class="col-xxl-3 col-md-6" id="<?= e($card['id']) ?>">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-1">
                            <div class="d-flex gap-4 align-items-center">
                                <div class="avatar-text avatar-lg bg-gray-200">
                                    <i class="<?= e($card['icon']) ?>"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-dark"><span class="counter"><?= e($card['value']) ?></span></div>
                                    <h3 class="fs-13 fw-semibold text-truncate-1-line"><?= e($card['label']) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Recent Activity</h5>
                </div>
                <div class="card-body custom-card-action p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="dashboard-activity-table">
                            <thead class="thead-light">
                                <tr>
                                    <th id="dashboard-activity-col-when">When</th>
                                    <th id="dashboard-activity-col-who">Who</th>
                                    <th id="dashboard-activity-col-what">What</th>
                                    <th id="dashboard-activity-col-where">Where</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($activity === []): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Nothing yet — activity appears here as the group uses the app.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($activity as $row): ?>
                                    <tr>
                                        <td><?= e(fmt_datetime($row['occurred_at'])) ?></td>
                                        <td><?= e($row['actor_label']) ?></td>
                                        <td><span class="badge bg-soft-info text-info"><?= e(str_replace('_', ' ', $row['action'])) ?></span></td>
                                        <td><small class="text-muted"><?= e($row['screen'] !== '' ? $row['screen'] : $row['entity']) ?></small></td>
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
