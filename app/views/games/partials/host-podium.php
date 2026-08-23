<?php
/** Host stage: final podium + full ranking. Vars: $game, $podium, $version. */
$id      = (int) $game['id'];
$pollUrl = '/games/' . $id . '/host-state?v=' . urlencode((string) $version);
$medals  = [1 => 'warning', 2 => 'secondary', 3 => 'danger'];
$top3    = array_filter($podium, static fn (array $p): bool => (int) $p['final_rank'] <= 3);
?>
<div id="game-host-stage"
     hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#game-host-stage" hx-swap="outerHTML">
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Podium</h5>
            <button type="button" id="game-host-finish-btn" class="btn btn-primary"
                    hx-post="/games/<?= $id ?>/advance" hx-vals='{"expected_state": "podium"}'
                    hx-target="#game-host-stage" hx-swap="outerHTML">
                <i class="feather-flag me-2"></i>
                <span>Finish Game</span>
            </button>
        </div>
        <div class="card-body">
            <div class="row justify-content-center text-center mb-4" id="game-host-podium-top3">
                <?php foreach ($top3 as $player): ?>
                    <div class="col-md-3" id="game-host-podium-rank-<?= e($player['final_rank']) ?>">
                        <div class="avatar-text avatar-xl bg-<?= e($medals[(int) $player['final_rank']] ?? 'secondary') ?> mx-auto mb-2">
                            <?= e($player['final_rank']) ?>
                        </div>
                        <h5 class="fw-bold mb-0"><?= e($player['nickname']) ?></h5>
                        <p class="fs-13 text-muted"><?= e(number_format((int) $player['final_score'])) ?> pts</p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="game-host-podium-table">
                    <thead class="thead-light">
                        <tr><th>#</th><th>Player</th><th class="text-end">Final Score</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($podium as $player): ?>
                            <tr id="game-host-podium-row-<?= e($player['id']) ?>">
                                <td class="fw-bold"><?= e($player['final_rank']) ?></td>
                                <td><?= e($player['nickname']) ?></td>
                                <td class="text-end fw-bold"><?= e(number_format((int) $player['final_score'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
