<?php
/** @var array $cohorts */
/** @var string $csrf */

use App\Core\View;
use App\Lib\DateConverter;
?>
<div class="container-wide">
    <div class="card">
        <h1>انتخاب کوهورت برای شروع درخواست</h1>

        <?php if ($cohorts === []): ?>
            <p class="help-text">در حال حاضر کوهورت بازی برای ثبت‌نام وجود ندارد.</p>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr><th>نام کوهورت</th><th>مهلت ثبت‌نام</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cohorts as $cohort): ?>
                        <tr>
                            <td data-label="نام"><?= View::e($cohort['name']) ?></td>
                            <td data-label="مهلت" class="ltr-num"><?= View::e(DateConverter::toJalaliDate($cohort['application_deadline']) ?: '-') ?></td>
                            <td data-label="">
                                <form method="post" action="/applications/start">
                                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                    <input type="hidden" name="cohort_id" value="<?= View::e($cohort['id']) ?>">
                                    <button type="submit" class="btn" style="width:auto">شروع درخواست</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
