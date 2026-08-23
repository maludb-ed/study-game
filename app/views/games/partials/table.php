<?php
/** Results region (table + pagination). Vars: $result, $filters. Root id sub-swaps. */
$total = $result['total'];
$pages = max(1, (int) ceil($total / GAMES_PAGE_SIZE));
$page  = min(max(1, (int) $filters['page']), $pages);
$qs = static function (int $toPage) use ($filters): string {
    return '/games/?' . http_build_query(array_filter([
        'exam_id' => $filters['exam_id'] ?: null,
        'page'    => $toPage > 1 ? $toPage : null,
    ]));
};
?>
<div class="col-lg-12" id="game-list-results">
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h5 class="card-title">Games <small class="text-muted">(<?= e($total) ?>)</small></h5>
        </div>
        <div class="card-body custom-card-action p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="game-list-table">
                    <thead class="thead-light">
                        <tr>
                            <th id="game-list-col-date">Date</th>
                            <th id="game-list-col-exam">Exam</th>
                            <th id="game-list-col-host">Host</th>
                            <th id="game-list-col-state">State</th>
                            <th id="game-list-col-players">Players</th>
                            <th id="game-list-col-pin">PIN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result['rows'] === []): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No games yet. Start a new game night.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($result['rows'] as $game): ?>
                            <?= view('games/partials/row.php', ['game' => $game]) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0" id="game-list-pagination">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e($qs($i)) ?>"
                                   hx-get="<?= e($qs($i)) ?>" hx-target="#game-list-results" hx-swap="outerHTML"><?= e($i) ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
