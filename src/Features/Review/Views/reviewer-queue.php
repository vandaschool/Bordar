<?php
/** @var array $rows */

use App\Core\View;

$statusLabels = ['ASSIGNED' => 'تخصیص‌یافته', 'DRAFT' => 'پیش‌نویس', 'SUBMITTED' => 'ثبت‌شده'];
?>
<div class="container-wide">
    <div class="card">
        <h1>درخواست‌های تخصیص‌یافته به من</h1>

        <?php if ($rows === []): ?>
            <p class="help-text">هنوز درخواستی به شما تخصیص نیافته است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>شرکت</th><th>کوهورت</th><th>وضعیت داوری</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td data-label="شرکت"><?= View::e($row['company']['name'] ?? '-') ?></td>
                            <td data-label="کوهورت"><?= View::e($row['cohort']['name'] ?? '-') ?></td>
                            <td data-label="وضعیت"><span class="badge"><?= View::e($statusLabels[$row['status']] ?? $row['status']) ?><?= $row['conflict_of_interest'] ? ' (تعارض منافع)' : '' ?></span></td>
                            <td data-label=""><a href="/reviewer/applications/<?= View::e($row['application_id']) ?>">مشاهده</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
