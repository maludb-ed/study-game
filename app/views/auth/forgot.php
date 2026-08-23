<?php /* Vars: $sent (bool) */ ?>
<h2 class="fs-20 fw-bolder mb-4" id="auth-forgot-title">Reset password</h2>
<?php if (!empty($sent)): ?>
    <div class="alert alert-success fs-12" id="auth-forgot-sent">If that address has an account, a reset link is on its way. Check your email.</div>
<?php else: ?>
    <h4 class="fs-13 fw-bold mb-2">Forgot your password?</h4>
    <p class="fs-12 fw-medium text-muted">Enter your email and we'll send a reset link.</p>
    <form action="/password/forgot" method="post" class="w-100 mt-4 pt-2" id="auth-forgot-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="mb-4">
            <input type="email" name="email" id="auth-forgot-field-email" class="form-control" placeholder="Email" required>
        </div>
        <div class="mt-4">
            <button type="submit" id="auth-forgot-submit-btn" class="btn btn-lg btn-primary w-100">Send reset link</button>
        </div>
    </form>
<?php endif; ?>
<div class="mt-5 text-muted">
    <a href="/login" id="auth-forgot-login-link" class="fw-bold">Back to login</a>
</div>
