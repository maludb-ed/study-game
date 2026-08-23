<?php
/**
 * Nickname step. Vars: $errors, $gameId, $nickname, $claimed, $user (?array — the
 * logged-in session's user row, or null for anonymous joiners).
 */
?>
<h2 class="fs-20 fw-bolder mb-4" id="join-nickname-title">Pick a Nickname</h2>
<p class="fs-12 fw-medium text-muted">This is what other players will see.</p>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger fs-12" id="join-nickname-errors">
        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>
<form action="/join/submit" method="post" class="w-100 mt-4 pt-2" id="join-nickname-form">
    <input type="hidden" name="game_id" value="<?= e($gameId) ?>">
    <div class="mb-4">
        <input type="text" name="nickname" id="join-field-nickname" class="form-control"
               minlength="2" maxlength="20" placeholder="Nickname"
               value="<?= e($nickname) ?>" required autofocus>
    </div>
    <?php if ($user !== null): ?>
        <div class="mb-4 form-check">
            <input type="checkbox" name="claim" value="1" id="join-field-claim" class="form-check-input" <?= $claimed ? 'checked' : '' ?>>
            <label class="form-check-label fs-12" for="join-field-claim">
                Count this game toward my readiness (<?= e($user['display_name']) ?>)
            </label>
        </div>
    <?php endif; ?>
    <div class="mt-5">
        <button type="submit" id="join-nickname-submit-btn" class="btn btn-lg btn-primary w-100">Join Game</button>
    </div>
</form>
