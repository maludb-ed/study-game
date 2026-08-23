<?php /* Vars: $errors (array), $old (array) */ ?>
<h2 class="fs-20 fw-bolder mb-4" id="auth-register-title">Register</h2>
<h4 class="fs-13 fw-bold mb-2">Create your account</h4>
<p class="fs-12 fw-medium text-muted">Join the study group. Use the email your group knows you by.</p>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger fs-12" id="auth-register-errors">
        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>
<form action="/register" method="post" class="w-100 mt-4 pt-2" id="auth-register-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="mb-4">
        <input type="text" name="display_name" id="auth-register-field-name" class="form-control" placeholder="Display name" value="<?= e($old['display_name'] ?? '') ?>" required>
    </div>
    <div class="mb-4">
        <input type="email" name="email" id="auth-register-field-email" class="form-control" placeholder="Email" value="<?= e($old['email'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
        <input type="password" name="password" id="auth-register-field-password" class="form-control" placeholder="Password (12+ characters)" required>
    </div>
    <div class="mt-5">
        <button type="submit" id="auth-register-submit-btn" class="btn btn-lg btn-primary w-100">Create Account</button>
    </div>
</form>
<div class="mt-5 text-muted">
    <span>Already have an account?</span>
    <a href="/login" id="auth-register-login-link" class="fw-bold">Login</a>
</div>
