<?php
/** Host stage: top-5 leaderboard with rank deltas. Vars: $game, $scoreboard, $isLast, $version. */
$id      = (int) $game['id'];
$pollUrl = '/games/' . $id . '/host-state?v=' . urlencode((string) $version);
$top     = array_slice($scoreboard, 0, 5);
?>
<div id="game-host-stage"
     hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#game-host-stage" hx-swap="outerHTML">
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Leaderboard <small class="text-muted">after question <?= e($game['current_position']) ?></small></h5>
            <button type="button" id="game-host-next-btn" class="btn btn-primary"
                    hx-post="/games/<?= $id ?>/advance" hx-vals='{"expected_state": "leaderboard"}'
                    hx-target="#game-host-stage" hx-swap="outerHTML">
                <span><?= $isLast ? 'Podium' : 'Next Question' ?></span>
                <i class="feather-arrow-right ms-2"></i>
            </button>
        </div>
        <div class="card-body custom-card-action p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="game-host-leaderboard-table">
                    <thead class="thead-light">
                        <tr><th>#</th><th>Player</th><th class="text-end">Points</th><th class="text-end">Move</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top as $row): ?>
                            <tr id="game-host-leaderboard-row-<?= e($row['id']) ?>">
                                <td class="fw-bold"><?= e($row['rank']) ?></td>
                                <td><?= e($row['nickname']) ?>
                                    <?php if ((int) $row['current_streak'] >= 2): ?>
                                        <span class="badge bg-soft-warning text-warning ms-2">streak <?= e($row['current_streak']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold"><?= e(number_format((int) $row['total'])) ?></td>
                                <td class="text-end">
                                    <?php if ((int) $row['rank_delta'] > 0): ?>
                                        <span class="text-success"><i class="feather-arrow-up"></i> <?= e($row['rank_delta']) ?></span>
                                    <?php elseif ((int) $row['rank_delta'] < 0): ?>
                                        <span class="text-danger"><i class="feather-arrow-down"></i> <?= e(abs((int) $row['rank_delta'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
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
