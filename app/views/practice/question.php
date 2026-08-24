<?php
/**
 * Practice question stage. Vars: $question (options+rationale), $index (0-based), $total,
 * $score, $answered, $revealed (bool), $chosenIds (int[] the player's selections).
 * A question with 2+ correct options is multi-select (all-or-nothing); otherwise single-tap.
 */
$optionColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
$chosenIds    = $chosenIds ?? [];
$correctCount = 0;
foreach ($question['options'] as $o) { if ($o['is_correct']) { $correctCount++; } }
$multi = $correctCount > 1;
?>
<div id="practice-stage">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-soft-warning text-warning">Question <?= (int) $index + 1 ?> / <?= (int) $total ?></span>
        <span class="fs-13 text-muted">Score: <?= (int) $score ?> / <?= (int) $answered ?></span>
    </div>
    <div class="card stretch stretch-full mb-3">
        <div class="card-body">
            <h4 class="fs-4 fw-bold mb-2" id="practice-stem"><?= e($question['stem']) ?></h4>
            <?php if ($multi && !$revealed): ?>
                <p class="fs-12 text-info mb-3"><i class="feather-check-square me-1"></i>Select all that apply, then submit.</p>
            <?php endif; ?>

            <?php if ($revealed): ?>
                <div id="practice-options">
                    <?php foreach ($question['options'] as $i => $opt):
                        $isCorrect = (bool) $opt['is_correct'];
                        $isChosen  = in_array((int) $opt['id'], array_map('intval', $chosenIds), true);
                        $cls = $isCorrect ? 'border-success bg-soft-success'
                             : ($isChosen ? 'border-danger bg-soft-danger' : 'border');
                        $rationale = trim((string) ($opt['rationale'] ?? ''));
                    ?>
                        <div class="card <?= $cls ?> mb-3" id="practice-opt-<?= (int) $opt['display_order'] ?>">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-text avatar-sm bg-<?= e($optionColors[$i] ?? 'secondary') ?> me-3"><?= (int) $opt['display_order'] ?></div>
                                    <span class="fs-6 flex-fill"><?= e($opt['option_text']) ?></span>
                                    <?php if ($isCorrect): ?>
                                        <span class="badge bg-success ms-2"><i class="feather-check me-1"></i>Correct<?= $isChosen ? ' — you picked this' : '' ?></span>
                                    <?php elseif ($isChosen): ?>
                                        <span class="badge bg-danger ms-2"><i class="feather-x me-1"></i>Your answer</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($rationale !== ''): ?>
                                    <p class="fs-12 <?= $isCorrect ? 'text-success' : 'text-muted' ?> mb-0 mt-2 ps-5"><?= e($rationale) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card bg-gray-200 border-0 mt-2 mb-0">
                    <div class="card-body">
                        <h6 class="fs-13 fw-bold"><i class="feather-book-open me-2"></i>Explanation</h6>
                        <p class="fs-13 mb-0"><?= e($question['explanation']) ?></p>
                    </div>
                </div>

            <?php elseif ($multi): ?>
                <form id="practice-answer-form">
                    <?php foreach ($question['options'] as $i => $opt): ?>
                        <input type="checkbox" class="btn-check" name="option_ids[]" autocomplete="off"
                               id="practice-choice-<?= (int) $opt['display_order'] ?>" value="<?= (int) $opt['id'] ?>">
                        <label class="btn btn-outline-<?= e($optionColors[$i] ?? 'secondary') ?> btn-lg w-100 py-3 mb-3 text-start"
                               style="white-space: normal; word-break: break-word;"
                               for="practice-choice-<?= (int) $opt['display_order'] ?>">
                            <span class="fw-bold me-2"><?= (int) $opt['display_order'] ?>.</span><?= e($opt['option_text']) ?>
                        </label>
                    <?php endforeach; ?>
                </form>
                <button type="button" class="btn btn-primary w-100 py-3" id="practice-submit-btn"
                        hx-post="/practice/answer" hx-include="#practice-answer-form"
                        hx-target="#page-content" hx-swap="innerHTML">
                    <i class="feather-check me-2"></i><span>Submit answer</span>
                </button>

            <?php else: ?>
                <div id="practice-options">
                    <?php foreach ($question['options'] as $i => $opt): ?>
                        <button type="button" class="btn btn-<?= e($optionColors[$i] ?? 'secondary') ?> btn-lg w-100 py-3 mb-3 text-start"
                                style="white-space: normal; word-break: break-word;"
                                id="practice-answer-<?= (int) $opt['display_order'] ?>"
                                hx-post="/practice/answer" hx-vals='{"option_id": <?= (int) $opt['id'] ?>}'
                                hx-target="#page-content" hx-swap="innerHTML">
                            <span class="fw-bold me-2"><?= (int) $opt['display_order'] ?>.</span>
                            <span><?= e($opt['option_text']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($revealed): ?>
            <div class="card-footer d-flex justify-content-end">
                <button type="button" class="btn btn-primary" id="practice-next-btn"
                        hx-post="/practice/next" hx-target="#page-content" hx-swap="innerHTML">
                    <span><?= (int) $index + 1 >= (int) $total ? 'See results' : 'Next question' ?></span>
                    <i class="feather-arrow-right ms-2"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
