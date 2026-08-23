<?php
/**
 * Game report screen (S4): page-header + main-content, three sections — header card,
 * podium/ranking (partials/report-ranking.php), per-question breakdown
 * (partials/report-questions.php). Vars: $game (with 'ranking'), $questionStats.
 * State vocabulary: lobby -> info, question/reveal/leaderboard -> warning,
 * podium/ended -> success, aborted -> danger.
 */
$stateColors = [
    'lobby' => 'info', 'question' => 'warning', 'reveal' => 'warning', 'leaderboard' => 'warning',
    'podium' => 'success', 'ended' => 'success', 'aborted' => 'danger',
];
$color = $stateColors[$game['state']] ?? 'secondary';
$id    = (int) $game['id'];
?>
<!-- [ page-header ] start -->
<div class="page-header" id="game-report-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Game Report</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/games/"
                hx-get="/games/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/games/">Games</a></li>
            <li class="breadcrumb-item"><?= e($game['exam_code']) ?> · <?= e(fmt_date($game['created_at'])) ?></li>
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
                <a href="/games/" id="game-report-back-btn" class="btn btn-light-brand"
                   hx-get="/games/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/games/">
                    <span>Back to Games</span>
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
<div class="main-content" id="game-report-content" data-screen="game-report" data-entity="games" data-record-id="<?= $id ?>">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full" id="game-report-header-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="badge bg-soft-primary text-primary fs-13"><?= e($game['exam_code']) ?></span>
                        <span class="fs-13 text-muted"><?= e($game['exam_name']) ?></span>
                        <span class="badge bg-soft-<?= e($color) ?> text-<?= e($color) ?>" id="game-report-state-badge">
                            <?= e(ucfirst($game['state'])) ?>
                        </span>
                    </div>
                    <div class="row mt-3">
                        <div class="col-sm-3">
                            <p class="text-muted fs-12 mb-1">Played</p>
                            <p class="fw-bold mb-0" id="game-report-played-date"><?= e(fmt_date($game['created_at'])) ?></p>
                        </div>
                        <div class="col-sm-3">
                            <p class="text-muted fs-12 mb-1">Host</p>
                            <p class="fw-bold mb-0" id="game-report-host-name"><?= e($game['host_name']) ?></p>
                        </div>
                        <div class="col-sm-3">
                            <p class="text-muted fs-12 mb-1">Settings</p>
                            <p class="fw-bold mb-0" id="game-report-settings">
                                <?= e($game['question_count']) ?> questions · <?= e($game['seconds_per_question']) ?>s · streak bonus <?= $game['streak_bonus'] ? 'on' : 'off' ?>
                            </p>
                        </div>
                        <div class="col-sm-3">
                            <p class="text-muted fs-12 mb-1">Players</p>
                            <p class="fw-bold mb-0" id="game-report-player-count"><?= e($game['player_count']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?= view('games/partials/report-ranking.php', ['game' => $game]) ?>
        <?= view('games/partials/report-questions.php', ['questionStats' => $questionStats]) ?>
    </div>
</div>
<!-- [ Main Content ] end -->
