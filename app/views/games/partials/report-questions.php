<?php
/**
 * Report section 3: per-question breakdown with option distribution mini-bars.
 * Vars: $questionStats (per find_game_question_stats: position, question_id, stem,
 * domain_name, correct_pct, avg_response_s, options => [option_text, display_order,
 * is_correct, picks, pct]).
 */
$optionColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
?>
<div class="col-lg-12" id="game-report-questions">
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h5 class="card-title">Questions</h5>
        </div>
        <div class="card-body custom-card-action p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="game-report-questions-table">
                    <thead class="thead-light">
                        <tr>
                            <th id="game-report-questions-col-number">#</th>
                            <th id="game-report-questions-col-stem">Question</th>
                            <th id="game-report-questions-col-domain">Domain</th>
                            <th class="text-end" id="game-report-questions-col-correct">Correct %</th>
                            <th class="text-end" id="game-report-questions-col-avg">Avg Response</th>
                            <th id="game-report-questions-col-distribution">Answer Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($questionStats === []): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No questions drawn.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($questionStats as $stat): ?>
                            <?php
                                $questionUrl  = '/questions/' . (int) $stat['question_id'];
                                $stem         = mb_strlen($stat['stem']) > 80 ? mb_substr($stat['stem'], 0, 80) . '…' : $stat['stem'];
                                $totalPicks   = array_sum(array_column($stat['options'], 'picks'));
                            ?>
                            <tr id="game-report-question-row-<?= e($stat['position']) ?>">
                                <td><?= e($stat['position']) ?></td>
                                <td id="game-report-question-row-<?= e($stat['position']) ?>-stem">
                                    <a href="<?= e($questionUrl) ?>"
                                       hx-get="<?= e($questionUrl) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($questionUrl) ?>">
                                        <?= e($stem) ?>
                                    </a>
                                </td>
                                <td><small class="text-muted"><?= e($stat['domain_name']) ?></small></td>
                                <td class="text-end"><?= e($stat['correct_pct']) ?>%</td>
                                <td class="text-end">
                                    <?= $stat['avg_response_s'] === null ? '—' : e(number_format((float) $stat['avg_response_s'], 1)) . 's' ?>
                                </td>
                                <td style="min-width: 220px;">
                                    <?php if ($totalPicks === 0): ?>
                                        <span class="fs-12 text-muted">No answers</span>
                                    <?php else: ?>
                                        <?php foreach ($stat['options'] as $index => $option): ?>
                                            <?php $optText = mb_strlen($option['option_text']) > 40 ? mb_substr($option['option_text'], 0, 40) . '…' : $option['option_text']; ?>
                                            <div class="d-flex align-items-center mb-1" id="game-report-question-row-<?= e($stat['position']) ?>-option-<?= e($option['display_order']) ?>">
                                                <span class="wd-10 ht-10 bg-<?= e($optionColors[$index] ?? 'secondary') ?> me-2 d-inline-block rounded-circle"></span>
                                                <span class="fs-12 flex-shrink-0 me-2" style="min-width: 90px;"><?= e($optText) ?></span>
                                                <div class="progress ht-3 flex-fill">
                                                    <div class="progress-bar bg-<?= $option['is_correct'] ? 'success' : 'secondary' ?>" role="progressbar"
                                                         style="width: <?= (int) $option['pct'] ?>%"></div>
                                                </div>
                                                <span class="fs-11 text-muted ms-2"><?= e($option['picks']) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
