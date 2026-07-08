<?php
/** @var array $cohorts */

use App\Core\View;
use App\Lib\DateConverter;

$statusLabels = [
    'UPCOMING' => 'در آینده',
    'ACTIVE' => 'فعال',
    'COMPLETED' => 'پایان‌یافته',
    'ARCHIVED' => 'بایگانی‌شده',
];
?>
<div class="container-wide">
    <div class="card">
        <div class="dashboard-card">
            <h1>مدیریت کوهورت‌ها</h1>
            <a class="btn" style="width:auto" href="/admin/cohorts/create">+ کوهورت جدید</a>
        </div>

        <?php if ($cohorts === []): ?>
            <p class="help-text">هنوز کوهورتی تعریف نشده است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>شروع</th>
                        <th>پایان</th>
                        <th>مهلت ثبت‌نام</th>
                        <th>وضعیت</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cohorts as $cohort): ?>
                        <tr>
                            <td data-label="نام"><?= View::e($cohort['name']) ?></td>
                            <td data-label="شروع" class="ltr-num"><?= View::e(DateConverter::toJalaliDate($cohort['start_date'])) ?></td>
                            <td data-label="پایان" class="ltr-num"><?= View::e(DateConverter::toJalaliDate($cohort['end_date'])) ?></td>
                            <td data-label="مهلت ثبت‌نام" class="ltr-num"><?= View::e(DateConverter::toJalaliDate($cohort['application_deadline']) ?: '-') ?></td>
                            <td data-label="وضعیت"><span class="badge"><?= View::e($statusLabels[$cohort['status']] ?? $cohort['status']) ?></span></td>
                            <td data-label=""><a href="/admin/cohorts/<?= View::e($cohort['id']) ?>/edit">ویرایش</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
