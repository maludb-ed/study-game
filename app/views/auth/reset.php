<?php /* Vars: $token (string), $errors (array) */ ?>
<h2 class="fs-20 fw-bolder mb-4" id="auth-reset-title">Choose a new password</h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger fs-12" id="auth-reset-errors">
        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>
<form action="/password/reset" method="post" class="w-100 mt-4 pt-2" id="auth-reset-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="mb-3">
        <input type="password" name="password" id="auth-reset-field-password" class="form-control" placeholder="New password (12+ characters)" required>
    </div>
    <div class="mt-4">
        <button type="submit" id="auth-reset-submit-btn" class="btn btn-lg btn-primary w-100">Set password</button>
    </div>
</form>
