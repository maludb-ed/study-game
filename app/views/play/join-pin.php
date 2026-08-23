<?php /** PIN entry step. Vars: $errors, $old. */ ?>
<h2 class="fs-20 fw-bolder mb-4" id="join-pin-title">Join a Game</h2>
<p class="fs-12 fw-medium text-muted">Enter the 6-digit PIN shown on the host's screen.</p>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger fs-12" id="join-pin-errors">
        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>
<form action="/join/submit" method="post" class="w-100 mt-4 pt-2" id="join-pin-form">
    <div class="mb-4">
        <input type="text" name="pin" id="join-field-pin" class="form-control form-control-lg text-center"
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6"
               placeholder="000000" value="<?= e($old['pin'] ?? '') ?>" required autofocus>
    </div>
    <div class="mt-5">
        <button type="submit" id="join-pin-submit-btn" class="btn btn-lg btn-primary w-100">Continue</button>
    </div>
</form>
