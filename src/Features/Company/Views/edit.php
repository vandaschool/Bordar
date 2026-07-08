<?php
/** @var array $errors */
/** @var array $company */
/** @var array $selected */
/** @var array $hsTree */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container">
    <div class="card">
        <h1>ویرایش پروفایل شرکت</h1>

        <form method="post" action="/company" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <input type="hidden" name="_method" value="PUT">

            <div class="form-group">
                <label for="name">نام شرکت</label>
                <input class="form-control" type="text" id="name" name="name" value="<?= View::e($company['name']) ?>" required>
                <?php foreach ($errors['name'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="registration_number">شماره ثبت شرکت</label>
                <input class="form-control ltr" type="text" id="registration_number" name="registration_number" value="<?= View::e($company['registration_number'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="industry">صنعت / حوزه فعالیت</label>
                <input class="form-control" type="text" id="industry" name="industry" value="<?= View::e($company['industry'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="country">کشور</label>
                <input class="form-control" type="text" id="country" name="country" value="<?= View::e($company['country'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="city">شهر</label>
                <input class="form-control" type="text" id="city" name="city" value="<?= View::e($company['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="website">وب‌سایت</label>
                <input class="form-control ltr" type="text" id="website" name="website" value="<?= View::e($company['website'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="description">توضیحات کوتاه</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= View::e($company['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>کدهای کالایی (HS Codes)</label>
                <?php require __DIR__ . '/_hs-tree.php'; ?>
            </div>

            <button type="submit" class="btn">ذخیره تغییرات</button>
        </form>
    </div>
</div>
