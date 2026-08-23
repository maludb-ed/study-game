<?php /* Success banner shown on the view screen right after a save. Vars: $savedAction ('created'|'updated') */ ?>
<div class="alert alert-success fs-12 d-flex align-items-center" id="question-saved-banner">
    <i class="feather-check-circle me-2"></i>
    <span>Question <?= e($savedAction) ?>.</span>
</div>
