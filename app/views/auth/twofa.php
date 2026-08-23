<?php /* Vars: $error (?string) */ ?>
<h2 class="fs-20 fw-bolder mb-4" id="auth-2fa-title">Two-factor authentication</h2>
<h4 class="fs-13 fw-bold mb-2">Enter the 6-digit code</h4>
<p class="fs-12 fw-medium text-muted">From your authenticator app — or use one of your recovery codes.</p>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger fs-12" id="auth-2fa-error"><?= e($error) ?></div>
<?php endif; ?>
<form action="/login/2fa" method="post" class="w-100 mt-4 pt-2" id="auth-2fa-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="mb-3">
        <input type="text" name="code" id="auth-2fa-field-code" class="form-control" placeholder="Code" autocomplete="one-time-code" inputmode="numeric" required autofocus>
    </div>
    <div class="mt-4">
        <button type="submit" id="auth-2fa-submit-btn" class="btn btn-lg btn-primary w-100">Verify</button>
    </div>
</form>
