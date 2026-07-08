<?php
/** @var array|null $auth */
/** @var array|null $role */
/** @var string $csrf */

use App\Core\View;

$roleName = $role['name'] ?? '-';
?>
<div class="container-wide">
    <div class="card dashboard-card">
        <div>
            <h1>خوش آمدید، <?= View::e(($auth['first_name'] ?? '') . ' ' . ($auth['last_name'] ?? '')) ?></h1>
            <p>نقش شما: <span class="badge"><?= View::e($roleName) ?></span></p>
        </div>
        <form method="post" action="/logout">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <button type="submit" class="btn btn-secondary" style="width:auto">خروج</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px">
        <?php if ($roleName === 'Admin'): ?>
            <h2 style="margin-top:0;font-size:1.05rem">میانبرهای مدیریتی</h2>
            <p><a href="/admin/cohorts">مدیریت کوهورت‌ها</a></p>
        <?php elseif ($roleName === 'Applicant'): ?>
            <h2 style="margin-top:0;font-size:1.05rem">میانبرها</h2>
            <p><a href="/company">پروفایل شرکت</a></p>
            <p><a href="/applications">درخواست‌های پذیرش</a></p>
        <?php endif; ?>
    </div>
</div>
