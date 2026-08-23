<?php /* Success banner on the view screen after a save. Vars: $savedAction */ ?>
<div class="alert alert-success fs-12 d-flex align-items-center" id="scenario-saved-banner">
    <i class="feather-check-circle me-2"></i>
    <span>Scenario <?= e($savedAction) ?>.</span>
</div>
