<?php
/**
 * Host console (game-host screen). Vars: $game, $players, $version.
 * The below-PIN area is div#game-host-stage (host-players.php) — the S2 poll target;
 * S3 swaps richer stages into the same id.
 */
$id = (int) $game['id'];
?>
<!-- [ page-header ] start -->
<div class="page-header" id="game-host-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Host Console</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/games/"
                hx-get="/games/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/games/">Games</a></li>
            <li class="breadcrumb-item">Host</li>
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
                <a href="/games/" id="game-host-back-btn" class="btn btn-light-brand"
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
<div class="main-content" id="game-host-content" data-screen="game-host" data-entity="games" data-record-id="<?= $id ?>">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body text-center py-5">
                    <p class="fs-13 text-muted mb-1" id="game-host-exam-label">
                        <?= e($game['exam_code']) ?> — <?= e($game['question_count']) ?> questions, <?= e($game['seconds_per_question']) ?>s each
                    </p>
                    <p class="text-muted mb-2">Game PIN</p>
                    <div class="display-1 fw-bolder" id="game-host-pin"><?= e($game['pin']) ?></div>
                    <p class="fs-16 mt-3" id="game-host-join-url">
                        Go to <strong><?= e(config('APP_URL')) ?>/join</strong>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <?= view('games/partials/host-players.php', ['game' => $game, 'players' => $players, 'version' => $version]) ?>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
