<?php
/** @var array $company */
/** @var array $hsCodes */
/** @var array $hsFlat */

use App\Core\View;
?>
<div class="container-wide">
    <div class="card">
        <div class="dashboard-card">
            <h1><?= View::e($company['name']) ?></h1>
            <a class="btn btn-secondary" style="width:auto" href="/company/edit">ویرایش</a>
        </div>

        <p><span class="badge"><?= View::e($company['status']) ?></span></p>

        <table class="table-list">
            <tbody>
                <tr><td data-label="صنعت"><?= View::e($company['industry'] ?? '-') ?></td></tr>
                <tr><td data-label="کشور / شهر" class="ltr-num"><?= View::e(trim(($company['country'] ?? '') . ' / ' . ($company['city'] ?? ''), ' /')) ?: '-' ?></td></tr>
                <tr><td data-label="وب‌سایت" class="ltr"><?= View::e($company['website'] ?? '-') ?></td></tr>
                <tr><td data-label="شماره ثبت" class="ltr"><?= View::e($company['registration_number'] ?? '-') ?></td></tr>
            </tbody>
        </table>

        <?php if ($company['description']): ?>
            <p style="margin-top:16px"><?= View::e($company['description']) ?></p>
        <?php endif; ?>

        <h2 style="margin-top:24px;font-size:1.1rem">کدهای کالایی</h2>
        <?php if ($hsCodes === []): ?>
            <p class="help-text">هنوز کد کالایی ثبت نشده است.</p>
        <?php else: ?>
            <div>
                <?php foreach ($hsCodes as $code): ?>
                    <span class="badge ltr" style="margin-inline-end:6px;margin-bottom:6px;display:inline-block"><?= View::e($code) ?> — <?= View::e($hsFlat[$code] ?? '') ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="help-text" style="margin-top:24px"><a href="/applications">مشاهده درخواست‌های پذیرش</a></p>
    </div>
</div>
