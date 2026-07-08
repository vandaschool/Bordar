<?php
/** @var array $sessions */
/** @var string $viewerTz */
/** @var string|null $status */

use App\Core\View;
use App\Lib\DateConverter;

$statusLabels = ['SCHEDULED' => 'برنامه‌ریزی‌شده', 'COMPLETED' => 'برگزارشده', 'CANCELED' => 'لغوشده', 'RESCHEDULED' => 'زمان‌بندی مجدد'];
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>

    <div class="card">
        <div class="dashboard-card">
            <h1>جلسات منتورینگ من</h1>
            <a class="btn" style="width:auto" href="/mentors">مشاهده منتورها</a>
        </div>

        <?php if ($sessions === []): ?>
            <p class="help-text">هنوز جلسه‌ای رزرو نکرده‌اید.</p>
        <?php else: ?>
            <?php foreach ($sessions as $s): ?>
                <div class="section-block"><div class="section-body">
                    <div class="dashboard-card">
                        <strong><?= View::e(($s['mentor_user']['first_name'] ?? '') . ' ' . ($s['mentor_user']['last_name'] ?? '')) ?></strong>
                        <span class="badge"><?= View::e($statusLabels[$s['status']] ?? $s['status']) ?></span>
                    </div>
                    <p class="ltr-num"><?= View::e(DateConverter::toJalali($s['start_time'], 'Y/m/d H:i', $viewerTz)) ?></p>
                    <?php if ($s['agenda']): ?><p><strong>موضوع:</strong> <?= View::e($s['agenda']) ?></p><?php endif; ?>
                    <?php if ($s['status'] === 'COMPLETED'): ?>
                        <p><strong>خلاصه جلسه:</strong> <?= View::e($s['summary'] ?? '-') ?></p>
                        <p><strong>برنامه اقدام:</strong> <?= View::e($s['action_plan'] ?? '-') ?></p>
                    <?php endif; ?>
                </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
