<?php
/** Practice landing: choose an exam to practice. Vars: $exams (with active_questions). */
?>
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Practice</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item">Practice</li>
        </ul>
    </div>
</div>
<div class="main-content" id="practice-landing">
    <p class="text-muted fs-13 mb-4">Pick an exam to practice solo. Questions are shuffled, and after each answer you'll see why every option is right or wrong.</p>
    <div class="row">
        <?php foreach ($exams as $exam): $n = (int) $exam['active_questions']; ?>
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card stretch stretch-full h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-soft-primary text-primary mb-2 align-self-start"><?= e($exam['code']) ?></span>
                        <h6 class="fw-bold mb-1"><?= e($exam['name']) ?></h6>
                        <p class="fs-12 text-muted flex-fill"><?= $n ?> active question<?= $n === 1 ? '' : 's' ?></p>
                        <?php if ($n > 0): ?>
                            <button type="button" class="btn btn-primary w-100 mt-2"
                                    id="practice-start-<?= (int) $exam['id'] ?>"
                                    hx-post="/practice/start" hx-vals='{"exam_id": <?= (int) $exam['id'] ?>}'
                                    hx-target="#page-content" hx-swap="innerHTML">
                                <i class="feather-play me-2"></i><span>Start practice</span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-light-brand w-100 mt-2" disabled>No questions yet</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
