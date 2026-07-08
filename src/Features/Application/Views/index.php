<?php
/** @var array $applications */
/** @var array $cohortNames */

use App\Core\View;
use App\Lib\DateConverter;

$statusLabels = [
    'DRAFT' => 'پیش‌نویس',
    'SUBMITTED' => 'ارسال‌شده',
    'UNDER_REVIEW' => 'در حال بررسی',
    'ACCEPTED' => 'پذیرفته‌شده',
    'REJECTED' => 'رد‌شده',
    'PENDING_INFO' => 'نیازمند تکمیل اطلاعات',
    'WITHDRAWN' => 'انصراف داده‌شده',
];
?>
<div class="container-wide">
    <div class="card">
        <div class="dashboard-card">
            <h1>درخواست‌های پذیرش</h1>
            <a class="btn" style="width:auto" href="/applications/start">+ درخواست جدید</a>
        </div>

        <?php if ($applications === []): ?>
            <p class="help-text">هنوز درخواستی ثبت نکرده‌اید.</p>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr><th>کوهورت</th><th>وضعیت</th><th>آخرین ویرایش</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td data-label="کوهورت"><?= View::e($cohortNames[$app['cohort_id']] ?? '-') ?></td>
                            <td data-label="وضعیت"><span class="badge"><?= View::e($statusLabels[$app['status']] ?? $app['status']) ?></span></td>
                            <td data-label="آخرین ویرایش" class="ltr-num"><?= View::e(DateConverter::toJalali($app['updated_at'])) ?></td>
                            <td data-label=""><a href="/applications/<?= View::e($app['id']) ?>">مشاهده</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
