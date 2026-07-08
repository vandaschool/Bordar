<?php
/** @var array|null $cohort */
/** @var array $errors */
/** @var array $old */
/** @var string $csrf */

use App\Core\View;

$isEdit = $cohort !== null;
$action = $isEdit ? '/admin/cohorts/' . $cohort['id'] : '/admin/cohorts';
?>
<div class="container">
    <div class="card">
        <h1><?= $isEdit ? 'ویرایش کوهورت' : 'کوهورت جدید' ?></h1>

        <form method="post" action="<?= View::e($action) ?>" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

            <div class="form-group">
                <label for="name">نام کوهورت</label>
                <input class="form-control" type="text" id="name" name="name" value="<?= View::e($old['name'] ?? '') ?>" required>
                <?php foreach ($errors['name'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="start_date">تاریخ شروع (شمسی، مثال: 1404/07/01)</label>
                <input class="form-control ltr" type="text" id="start_date" name="start_date" value="<?= View::e($old['start_date'] ?? '') ?>" placeholder="1404/07/01" required>
                <?php foreach ($errors['start_date'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="end_date">تاریخ پایان (شمسی)</label>
                <input class="form-control ltr" type="text" id="end_date" name="end_date" value="<?= View::e($old['end_date'] ?? '') ?>" placeholder="1404/10/01" required>
                <?php foreach ($errors['end_date'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="application_deadline">مهلت ثبت‌نام (شمسی، اختیاری)</label>
                <input class="form-control ltr" type="text" id="application_deadline" name="application_deadline" value="<?= View::e($old['application_deadline'] ?? '') ?>" placeholder="1404/06/15">
            </div>

            <div class="form-group">
                <label for="status">وضعیت</label>
                <select class="form-control" id="status" name="status">
                    <?php foreach (['UPCOMING' => 'در آینده', 'ACTIVE' => 'فعال', 'COMPLETED' => 'پایان‌یافته', 'ARCHIVED' => 'بایگانی‌شده'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($old['status'] ?? 'UPCOMING') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">توضیحات</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= View::e($old['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد کوهورت' ?></button>
        </form>
    </div>
</div>
