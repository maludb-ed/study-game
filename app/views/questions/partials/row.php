<?php
/** One list row. Vars: $question. */
$statusColors = ['draft' => 'dark', 'active' => 'success', 'retired' => 'secondary'];
$color = $statusColors[$question['status']] ?? 'secondary';
$stem  = mb_strlen($question['stem']) > 80 ? mb_substr($question['stem'], 0, 80) . '…' : $question['stem'];
$url   = '/questions/' . (int) $question['id'];
?>
<tr id="question-row-<?= e($question['id']) ?>">
    <td id="question-row-<?= e($question['id']) ?>-stem">
        <a href="<?= e($url) ?>"
           hx-get="<?= e($url) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($url) ?>">
            <span class="wd-10 ht-10 bg-<?= e($color) ?> me-2 d-inline-block rounded-circle"></span>
            <span><?= e($stem) ?></span>
        </a>
    </td>
    <td><span class="badge bg-soft-primary text-primary"><?= e($question['exam_code']) ?></span></td>
    <td><small class="text-muted"><?= e($question['domain_name']) ?></small></td>
    <td><?= e(ucfirst($question['difficulty'])) ?></td>
    <td><span class="badge bg-soft-<?= e($color) ?> text-<?= e($color) ?>"><?= e(ucfirst($question['status'])) ?></span></td>
    <td><?= e(fmt_date($question['updated_at'])) ?></td>
    <td class="text-end">
        <a href="<?= e($url) ?>/edit" id="question-row-<?= e($question['id']) ?>-edit-btn" class="avatar-text avatar-md"
           hx-get="<?= e($url) ?>/edit" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($url) ?>/edit">
            <i class="feather-edit"></i>
        </a>
    </td>
</tr>
