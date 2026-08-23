<?php
/**
 * Terminal notice: kicked, or the game ended/aborted. Vars: $message.
 * Returned with HTTP 286 by state.php (HTMX stops polling) — no poll attributes here.
 */
?>
<div id="play-stage">
    <h2 class="fs-20 fw-bolder mb-3" id="play-terminal-title">Game Over</h2>
    <p class="fs-14" id="play-terminal-message"><?= e($message) ?></p>
    <a href="/join" id="play-terminal-join-link" class="btn btn-primary mt-3">Join another game</a>
</div>
