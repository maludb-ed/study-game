<?php /* Vars: $screen (string), $screenTitle (string), $slice (string). */ ?>
<!-- [ page-header ] start -->
<div class="page-header" id="<?= e($screen) ?>-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10"><?= e($screenTitle) ?></h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><?= e($screenTitle) ?></li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="<?= e($screen) ?>-content" data-screen="<?= e($screen) ?>">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body text-center py-5">
                    <div class="avatar-text avatar-lg bg-gray-200 mx-auto mb-3">
                        <i class="feather-tool"></i>
                    </div>
                    <h5 class="fw-bold"><?= e($screenTitle) ?> is on the way</h5>
                    <p class="fs-12 text-muted mb-0">This screen arrives with <?= e($slice) ?>. The navigation is wired; the feature lands in build order.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
