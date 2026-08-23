<?php
/**
 * One list row. Vars: $game.
 * State vocabulary: lobby -> info, question/reveal/leaderboard -> warning,
 * podium/ended -> success, aborted -> danger.
 */
$stateColors = [
    'lobby' => 'info', 'question' => 'warning', 'reveal' => 'warning', 'leaderboard' => 'warning',
    'podium' => 'success', 'ended' => 'success', 'aborted' => 'danger',
];
$color  = $stateColors[$game['state']] ?? 'secondary';
$isLive = !in_array($game['state'], GAMES_TERMINAL_STATES, true);
$url    = '/games/' . (int) $game['id'] . '/host';
?>
<tr id="game-row-<?= e($game['id']) ?>">
    <td id="game-row-<?= e($game['id']) ?>-date">
        <?php if ($isLive): ?>
            <a href="<?= e($url) ?>"
               hx-get="<?= e($url) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($url) ?>">
                <?= e(fmt_date($game['created_at'])) ?>
            </a>
        <?php elseif ($game['state'] === 'ended'): ?>
            <span data-bs-toggle="tooltip" title="Report arrives with S4"><?= e(fmt_date($game['created_at'])) ?></span>
        <?php else: ?>
            <span><?= e(fmt_date($game['created_at'])) ?></span>
        <?php endif; ?>
    </td>
    <td><span class="badge bg-soft-primary text-primary"><?= e($game['exam_code']) ?></span></td>
    <td><?= e($game['host_name']) ?></td>
    <td>
        <span class="wd-10 ht-10 bg-<?= e($color) ?> me-2 d-inline-block rounded-circle"></span>
        <span class="badge bg-soft-<?= e($color) ?> text-<?= e($color) ?>"><?= e(ucfirst($game['state'])) ?></span>
    </td>
    <td><?= e($game['player_count']) ?></td>
    <td><?= $isLive ? e($game['pin']) : '—' ?></td>
</tr>
