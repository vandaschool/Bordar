<?php
/** @var array $ticket */
/** @var array $messages */
/** @var bool $isStaff */
/** @var array $statuses */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;
use App\Lib\DateConverter;

$statusLabels = ['OPEN' => 'باز', 'IN_PROGRESS' => 'در حال بررسی', 'RESOLVED' => 'حل‌شده', 'CLOSED' => 'بسته‌شده', 'ESCALATED' => 'ارجاع‌شده', 'PENDING_USER_RESPONSE' => 'در انتظار پاسخ متقاضی'];
$error = Session::flash('error');
$replyAction = $isStaff ? "/staff/tickets/{$ticket['id']}/reply" : "/tickets/{$ticket['id']}/reply";
?>
<div class="container-wide">
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="dashboard-card">
            <h1><?= View::e($ticket['subject']) ?></h1>
            <span class="badge"><?= View::e($statusLabels[$ticket['status']] ?? $ticket['status']) ?></span>
        </div>
        <?php if ($isStaff): ?>
            <p class="help-text">درخواست‌کننده: <?= View::e(($ticket['requester']['first_name'] ?? '') . ' ' . ($ticket['requester']['last_name'] ?? '')) ?></p>
        <?php endif; ?>
        <p><?= nl2br(View::e($ticket['description'])) ?></p>

        <?php if ($isStaff): ?>
            <div class="cta-row" style="justify-content:flex-start;margin-top:10px">
                <form method="post" action="/staff/tickets/<?= View::e($ticket['id']) ?>/assign">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                    <button type="submit" class="btn btn-secondary" style="width:auto">تخصیص به من</button>
                </form>
                <form method="post" action="/staff/tickets/<?= View::e($ticket['id']) ?>/status">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                    <select name="status" class="form-control" style="width:auto;display:inline-block" onchange="this.form.submit()">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= $statusLabels[$s] ?? $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">مکالمه</h2>
        <?php foreach ($messages as $m): ?>
            <div class="section-block <?= $m['is_internal'] ? 'internal-note' : '' ?>"><div class="section-body">
                <?php if ($m['is_internal']): ?><span class="badge" style="background:#fffbeb;color:#b45309">یادداشت داخلی</span><?php endif; ?>
                <p style="margin-bottom:4px"><?= nl2br(View::e($m['message'])) ?></p>
                <span class="help-text ltr-num"><?= View::e(DateConverter::relativeJalali($m['created_at'])) ?></span>
            </div></div>
        <?php endforeach; ?>

        <form method="post" action="<?= View::e($replyAction) ?>" style="margin-top:14px">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="message">پاسخ</label>
                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
            </div>
            <?php if ($isStaff): ?>
                <div class="form-group">
                    <label><input type="checkbox" name="is_internal" value="1"> یادداشت داخلی (فقط برای تیم قابل مشاهده است)</label>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn">ارسال پاسخ</button>
        </form>
    </div>
</div>
