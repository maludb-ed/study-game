<?php
/**
 * Report section 2: podium (top 3) + full ranking table. Vars: $game (with 'ranking').
 * Ranking rows: id, nickname, user_id, member_name (claimed identity, or null),
 * rank, total, correct_count, answered_count, best_streak.
 */
$ranking = $game['ranking'];
$medals  = [1 => 'warning', 2 => 'secondary', 3 => 'danger'];
$top3    = array_filter($ranking, static fn (array $row): bool => (int) $row['rank'] <= 3);
?>
<div class="col-lg-12" id="game-report-ranking">
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h5 class="card-title">Ranking</h5>
        </div>
        <div class="card-body">
            <?php if ($ranking === []): ?>
                <p class="text-muted text-center py-4 mb-0" id="game-report-ranking-empty">No players in this game.</p>
            <?php else: ?>
                <div class="row justify-content-center text-center mb-4" id="game-report-podium-top3">
                    <?php foreach ($top3 as $row): ?>
                        <div class="col-md-3" id="game-report-podium-rank-<?= e($row['rank']) ?>">
                            <div class="avatar-text avatar-xl bg-<?= e($medals[(int) $row['rank']] ?? 'secondary') ?> mx-auto mb-2">
                                <?= e($row['rank']) ?>
                            </div>
                            <h5 class="fw-bold mb-0"><?= e($row['nickname']) ?></h5>
                            <p class="fs-13 text-muted"><?= e(number_format((int) $row['total'])) ?> pts</p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="game-report-ranking-table">
                        <thead class="thead-light">
                            <tr>
                                <th id="game-report-ranking-col-rank">#</th>
                                <th id="game-report-ranking-col-player">Player</th>
                                <th class="text-end" id="game-report-ranking-col-points">Points</th>
                                <th class="text-end" id="game-report-ranking-col-correct">Correct</th>
                                <th class="text-end" id="game-report-ranking-col-streak">Best Streak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $row): ?>
                                <tr id="game-report-rank-row-<?= e($row['id']) ?>">
                                    <td class="fw-bold"><?= e($row['rank']) ?></td>
                                    <td>
                                        <?= e($row['nickname']) ?>
                                        <?php if (!empty($row['member_name'])): ?>
                                            <small class="text-muted">(<?= e($row['member_name']) ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold"><?= e(number_format((int) $row['total'])) ?></td>
                                    <td class="text-end"><?= e($row['correct_count']) ?>/<?= e($game['question_count']) ?></td>
                                    <td class="text-end"><?= e($row['best_streak']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
