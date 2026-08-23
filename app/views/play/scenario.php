<?php /* Player stage: scenario intro — read the host screen. Vars: $game, $player, $gq, $version. */
$pollUrl = '/play/state?v=' . urlencode((string) $version);
?>
<div id="play-stage" hx-get="<?= e($pollUrl) ?>" hx-trigger="every 1s" hx-target="#play-stage" hx-swap="outerHTML">
    <div class="avatar-text avatar-xl bg-info mx-auto mb-3"><i class="feather-book-open"></i></div>
    <h2 class="fs-20 fw-bolder mb-2 text-center" id="play-scenario-title">Scenario time</h2>
    <p class="fs-14 text-center" id="play-scenario-name"><strong><?= e($gq['scenario_title']) ?></strong></p>
    <p class="fs-12 text-muted text-center" id="play-scenario-hint">
        Read it on the host screen — the answer buttons appear when the host starts the question.
    </p>
</div>
