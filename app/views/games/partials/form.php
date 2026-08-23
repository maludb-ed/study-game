<?php
/**
 * game-new form (full-container). Vars: $exams (active exams w/ active_questions counts),
 * $examId, $questionCount, $seconds, $streakBonus, $errors.
 */
?>
<!-- [ page-header ] start -->
<div class="page-header" id="game-form-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">New Game</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/games/"
                hx-get="/games/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/games/">Games</a></li>
            <li class="breadcrumb-item">New Game</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="page-header-right-items">
            <div class="d-flex d-md-none">
                <a href="javascript:void(0)" class="page-header-right-close-toggle">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                <a href="/games/" id="game-form-cancel-btn" class="btn btn-light-brand"
                   hx-get="/games/" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="/games/">
                    <span>Cancel</span>
                </a>
                <button type="submit" form="game-form" id="game-form-save-btn" class="btn btn-primary">
                    <i class="feather-play me-2"></i>
                    <span>Start Game</span>
                </button>
            </div>
        </div>
        <div class="d-md-none d-flex align-items-center">
            <a href="javascript:void(0)" class="page-header-right-open-toggle">
                <i class="feather-align-right fs-20"></i>
            </a>
        </div>
    </div>
</div>
<!-- [ page-header ] end -->
<!-- [ Main Content ] start -->
<div class="main-content" id="game-form-content" data-screen="game-new">
    <div class="row">
        <div class="col-lg-8" id="game-form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fs-12" id="game-form-errors">
                    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form id="game-form" action="/games/save" method="post"
                  hx-post="/games/save" hx-target="#page-content" hx-swap="innerHTML">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="mb-4 row" id="game-form-field-exam-row">
                            <label class="col-lg-4 col-form-label" for="game-form-field-exam">Exam</label>
                            <div class="col-lg-8">
                                <select name="exam_id" id="game-form-field-exam" class="form-select" required>
                                    <option value="">Choose an exam…</option>
                                    <?php foreach ($exams as $exam): ?>
                                        <?php $short = (int) $exam['active_questions'] < $questionCount; ?>
                                        <option value="<?= e($exam['id']) ?>"
                                                <?= (int) $examId === (int) $exam['id'] ? 'selected' : '' ?>
                                                <?= $short ? 'disabled' : '' ?>>
                                            <?= e($exam['code']) ?> — <?= e($exam['name']) ?><?php if ($short): ?> (only <?= e($exam['active_questions']) ?> active — need <?= e($questionCount) ?>)<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4 row" id="game-form-field-count-row">
                            <label class="col-lg-4 col-form-label" for="game-form-field-count">Number of Questions</label>
                            <div class="col-lg-8">
                                <input type="number" name="question_count" id="game-form-field-count" class="form-control"
                                       min="5" max="30" value="<?= e($questionCount) ?>" required>
                            </div>
                        </div>
                        <div class="mb-4 row" id="game-form-field-seconds-row">
                            <label class="col-lg-4 col-form-label" for="game-form-field-seconds">Seconds per Question</label>
                            <div class="col-lg-8">
                                <select name="seconds_per_question" id="game-form-field-seconds" class="form-select" required>
                                    <?php foreach ([10, 20, 30, 60] as $secondsOption): ?>
                                        <option value="<?= $secondsOption ?>" <?= $seconds === $secondsOption ? 'selected' : '' ?>>
                                            <?= $secondsOption ?> seconds
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2 row" id="game-form-field-streak-row">
                            <label class="col-lg-4 col-form-label">Streak Bonus</label>
                            <div class="col-lg-8">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="streak_bonus" value="1" id="game-form-field-streak"
                                           class="form-check-input" <?= $streakBonus ? 'checked' : '' ?>>
                                    <label class="form-check-label fs-12" for="game-form-field-streak">Award bonus points for answer streaks</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->
