<?php /* Exams list screen. Vars: $exams (with counts). */ ?>
<!-- [ page-header ] start -->
<div class="page-header" id="exam-list-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Exams &amp; Coverage</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Exams</li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="exam-list-content" data-screen="exam-list">
    <div class="row">
        <?= view('exams/partials/exams-table.php', ['exams' => $exams]) ?>
    </div>
</div>
<!-- [ Main Content ] end -->
