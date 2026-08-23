<?php /* Vars: $mode ('setup'|'enabled'|'codes'), $secret, $qrDataUri, $errors, $recoveryCodes, $enabledAt */ ?>
<!-- [ page-header ] start -->
<div class="page-header" id="settings-2fa-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Two-Factor Authentication</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Settings</li>
            <li class="breadcrumb-item">Two-Factor Auth</li>
        </ul>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="settings-2fa-content" data-screen="settings-2fa">
    <div class="row">
        <div class="col-lg-8">
            <div class="card stretch stretch-full">
                <?php if (!empty($errors)): ?>
                    <div class="card-body pb-0">
                        <div class="alert alert-danger fs-12" id="settings-2fa-errors">
                            <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($mode === 'setup'): ?>
                    <div class="card-header">
                        <h5 class="card-title">Enable authenticator-app 2FA</h5>
                    </div>
                    <div class="card-body">
                        <p class="fs-12 text-muted">Scan the QR code with your authenticator app (Google Authenticator, 1Password, Authy…), or enter the key manually. Then confirm with a 6-digit code.</p>
                        <div class="text-center mb-4" id="settings-2fa-qr">
                            <img src="<?= e($qrDataUri) ?>" alt="TOTP enrollment QR code" class="img-fluid" width="220">
                        </div>
                        <p class="fs-12 text-center">Manual key: <code id="settings-2fa-manual-key"><?= e($secret) ?></code></p>
                        <form action="/settings/2fa" method="post" id="settings-2fa-enable-form" class="row justify-content-center g-2">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="do" value="enable">
                            <div class="col-auto">
                                <input type="text" name="code" id="settings-2fa-field-code" class="form-control" placeholder="6-digit code" inputmode="numeric" autocomplete="one-time-code" required>
                            </div>
                            <div class="col-auto">
                                <button type="submit" id="settings-2fa-enable-btn" class="btn btn-primary">Enable 2FA</button>
                            </div>
                        </form>
                    </div>
                <?php elseif ($mode === 'codes'): ?>
                    <div class="card-header">
                        <h5 class="card-title">2FA enabled — save your recovery codes</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning fs-12">These 10 single-use recovery codes are shown <strong>once</strong>. Store them somewhere safe — each one can replace a code if you lose your authenticator.</div>
                        <div class="row" id="settings-2fa-recovery-codes">
                            <?php foreach ($recoveryCodes as $index => $code): ?>
                                <div class="col-6 col-md-4 mb-2"><code><?= e($code) ?></code></div>
                            <?php endforeach; ?>
                        </div>
                        <a href="/settings/2fa" class="btn btn-primary mt-3" id="settings-2fa-done-btn">Done</a>
                    </div>
                <?php else: ?>
                    <div class="card-header">
                        <h5 class="card-title">Two-factor authentication is on</h5>
                    </div>
                    <div class="card-body">
                        <p class="fs-12 text-muted">Enabled <?= e(fmt_date($enabledAt)) ?>. Every login — password or Google — asks for a code.</p>
                        <h6 class="fs-13 fw-bold mt-4">Disable 2FA</h6>
                        <p class="fs-12 text-muted">Requires a current authenticator code (or a recovery code).</p>
                        <form action="/settings/2fa" method="post" id="settings-2fa-disable-form" class="row g-2">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="do" value="disable">
                            <div class="col-auto">
                                <input type="text" name="code" id="settings-2fa-field-disable-code" class="form-control" placeholder="Code" autocomplete="one-time-code" required>
                            </div>
                            <div class="col-auto">
                                <button type="submit" id="settings-2fa-disable-btn" class="btn btn-danger"
                                        hx-confirm="Disable two-factor authentication?">Disable</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
