<?php
/** @var array $applications */
/** @var array $reviewers */

use App\Core\Session;
use App\Core\View;

$statusLabels = [
    'SUBMITTED' => 'ارسال‌شده',
    'UNDER_REVIEW' => 'در حال بررسی',
    'PENDING_INFO' => 'نیازمند شفاف‌سازی',
];
$status = Session::flash('status');
$error = Session::flash('error');
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <h1>صف داوری</h1>

        <?php if ($applications === []): ?>
            <p class="help-text">در حال حاضر درخواستی برای داوری وجود ندارد.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>شرکت</th><th>کوهورت</th><th>وضعیت</th><th>تعداد داور</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td data-label="شرکت"><?= View::e($app['company']['name'] ?? '-') ?></td>
                            <td data-label="کوهورت"><?= View::e($app['cohort']['name'] ?? '-') ?></td>
                            <td data-label="وضعیت"><span class="badge"><?= View::e($statusLabels[$app['status']] ?? $app['status']) ?></span></td>
                            <td data-label="داوران" class="ltr-num"><?= View::e((string) $app['reviewer_count']) ?></td>
                            <td data-label=""><a href="/admin/reviews/<?= View::e($app['id']) ?>">مدیریت</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">بار کاری داوران</h2>
        <?php if ($reviewers === []): ?>
            <p class="help-text">هنوز داوری تعریف نشده است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>داور</th><th>بار کاری فعال</th></tr></thead>
                <tbody>
                    <?php foreach ($reviewers as $r): ?>
                        <tr>
                            <td data-label="داور"><?= View::e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                            <td data-label="بار کاری" class="ltr-num"><?= View::e((string) $r['active_workload']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
