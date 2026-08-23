<?php /* Vars: $error (?string), $flash (?array), $googleEnabled (bool) */ ?>
<h2 class="fs-20 fw-bolder mb-4" id="auth-login-title">Login</h2>
<h4 class="fs-13 fw-bold mb-2">Login to your account</h4>
<p class="fs-12 fw-medium text-muted">Study-group access for the Claude certification game nights.</p>
<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['kind']) ?> fs-12" id="auth-login-flash"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger fs-12" id="auth-login-error"><?= e($error) ?></div>
<?php endif; ?>
<form action="/login" method="post" class="w-100 mt-4 pt-2" id="auth-login-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="mb-4">
        <input type="email" name="email" id="auth-login-field-email" class="form-control" placeholder="Email" required>
    </div>
    <div class="mb-3">
        <input type="password" name="password" id="auth-login-field-password" class="form-control" placeholder="Password" required>
    </div>
    <div class="d-flex align-items-center justify-content-between">
        <div></div>
        <div>
            <a href="/password/forgot" id="auth-login-forgot-link" class="fs-11 text-primary">Forget password?</a>
        </div>
    </div>
    <div class="mt-5">
        <button type="submit" id="auth-login-submit-btn" class="btn btn-lg btn-primary w-100">Login</button>
    </div>
</form>
<?php if (!empty($googleEnabled)): ?>
    <div class="w-100 mt-5 text-center mx-auto">
        <div class="mb-4 border-bottom position-relative"><span class="small py-1 px-3 text-uppercase text-muted bg-white position-absolute translate-middle">or</span></div>
        <div class="d-flex align-items-center justify-content-center gap-2">
            <a href="/auth/google/start" id="auth-login-google-btn" class="btn btn-light-brand flex-fill">
                <i class="feather-chrome me-2"></i>
                <span>Sign in with Google</span>
            </a>
        </div>
    </div>
<?php endif; ?>
<div class="mt-5 text-muted">
    <span>Don't have an account?</span>
    <a href="/register" id="auth-login-register-link" class="fw-bold">Create an Account</a>
</div>
