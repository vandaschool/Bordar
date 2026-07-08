<?php
/** @var array $courses */
/** @var array $cohorts */
/** @var string|null $status */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>

    <div class="card">
        <h1>مدیریت مرکز آموزش</h1>

        <details>
            <summary style="cursor:pointer">+ دوره جدید</summary>
            <form method="post" action="/admin/lms" style="margin-top:12px">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="form-group">
                    <label for="title">عنوان دوره</label>
                    <input class="form-control" type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">توضیحات</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="cohort_id">کوهورت</label>
                    <select class="form-control" id="cohort_id" name="cohort_id">
                        <option value="">-- عمومی (همه کوهورت‌ها) --</option>
                        <?php foreach ($cohorts as $cohort): ?>
                            <option value="<?= View::e($cohort['id']) ?>"><?= View::e($cohort['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn" style="width:auto">ایجاد دوره</button>
            </form>
        </details>
    </div>

    <div class="card" style="margin-top:16px">
        <?php if ($courses === []): ?>
            <p class="help-text">هنوز دوره‌ای ایجاد نشده است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>عنوان</th><th>کوهورت</th><th>تعداد درس</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                        <tr>
                            <td data-label="عنوان"><?= View::e($c['title']) ?></td>
                            <td data-label="کوهورت"><?= View::e($c['cohort']['name'] ?? 'عمومی') ?></td>
                            <td data-label="تعداد درس" class="ltr-num"><?= View::e((string) $c['lesson_count']) ?></td>
                            <td data-label=""><a href="/admin/lms/<?= View::e($c['id']) ?>">مدیریت دروس</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
