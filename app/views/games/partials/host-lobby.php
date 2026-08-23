<?php
/**
 * Host console SCREEN SHELL (S3 restructure): page-header + main-content wrapping
 * whatever stage is current. Vars: $game, $stageHtml (a #game-host-stage root
 * rendered from host-players/host-question/host-reveal/host-leaderboard/host-podium).
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
<?= $stageHtml ?>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
