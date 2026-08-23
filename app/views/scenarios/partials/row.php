<?php
/** One list row. Vars: $scenario. */
$statusColors = ['draft' => 'dark', 'active' => 'success', 'retired' => 'secondary'];
$color = $statusColors[$scenario['status']] ?? 'secondary';
$title = mb_strlen($scenario['title']) > 70 ? mb_substr($scenario['title'], 0, 70) . '…' : $scenario['title'];
$url   = '/scenarios/' . (int) $scenario['id'];
?>
<tr id="scenario-row-<?= e($scenario['id']) ?>">
    <td id="scenario-row-<?= e($scenario['id']) ?>-title">
        <a href="<?= e($url) ?>"
           hx-get="<?= e($url) ?>" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($url) ?>">
            <span class="wd-10 ht-10 bg-<?= e($color) ?> me-2 d-inline-block rounded-circle"></span>
            <span><?= e($title) ?></span>
        </a>
    </td>
    <td><span class="badge bg-soft-primary text-primary"><?= e($scenario['exam_code']) ?></span></td>
    <td><span class="badge bg-soft-<?= e($color) ?> text-<?= e($color) ?>"><?= e(ucfirst($scenario['status'])) ?></span></td>
    <td><?= e($scenario['question_count']) ?></td>
    <td><?= e(fmt_date($scenario['updated_at'])) ?></td>
    <td class="text-end">
        <a href="<?= e($url) ?>/edit" id="scenario-row-<?= e($scenario['id']) ?>-edit-btn" class="avatar-text avatar-md"
           hx-get="<?= e($url) ?>/edit" hx-target="#page-content" hx-swap="innerHTML" hx-push-url="<?= e($url) ?>/edit">
            <i class="feather-edit"></i>
        </a>
    </td>
</tr>
