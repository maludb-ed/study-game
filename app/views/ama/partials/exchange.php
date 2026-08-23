<?php /* One Q/A exchange appended to #ama-thread. Vars: $question, $answer. */ ?>
<div class="mb-3 ama-exchange">
    <div class="d-flex justify-content-end mb-2">
        <div class="bg-primary text-white rounded p-2 fs-13 col-11 col-lg-8 w-auto"><?= e($question) ?></div>
    </div>
    <div class="d-flex">
        <div class="bg-gray-200 rounded p-2 fs-13 col-11 col-lg-8 w-auto"><?= nl2br(e($answer)) ?></div>
    </div>
</div>
