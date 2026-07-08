<?php
/** @var array $tickets */

use App\Core\View;
use App\Lib\DateConverter;

$statusLabels = ['OPEN' => 'باز', 'IN_PROGRESS' => 'در حال بررسی', 'RESOLVED' => 'حل‌شده', 'CLOSED' => 'بسته‌شده', 'ESCALATED' => 'ارجاع‌شده', 'PENDING_USER_RESPONSE' => 'در انتظار پاسخ متقاضی'];
$priorityLabels = ['LOW' => 'کم', 'MEDIUM' => 'متوسط', 'HIGH' => 'زیاد', 'URGENT' => 'فوری'];
?>
<div class="container-wide">
    <div class="card">
        <h1>صف تیکت‌های پشتیبانی</h1>

        <?php if ($tickets === []): ?>
            <p class="help-text">تیکت باز/در جریانی وجود ندارد.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>موضوع</th><th>درخواست‌کننده</th><th>اولویت</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td data-label="موضوع"><?= View::e($t['subject']) ?></td>
                            <td data-label="درخواست‌کننده"><?= View::e(($t['requester']['first_name'] ?? '') . ' ' . ($t['requester']['last_name'] ?? '')) ?></td>
                            <td data-label="اولویت"><?= View::e($priorityLabels[$t['priority']] ?? $t['priority']) ?></td>
                            <td data-label="وضعیت"><span class="badge"><?= View::e($statusLabels[$t['status']] ?? $t['status']) ?></span></td>
                            <td data-label=""><a href="/staff/tickets/<?= View::e($t['id']) ?>">مشاهده</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
