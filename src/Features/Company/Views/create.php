<?php
/** @var array $errors */
/** @var array $old */
/** @var array $hsTree */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container">
    <div class="card">
        <h1>تکمیل پروفایل شرکت</h1>
        <p>پیش از ارسال درخواست پذیرش، اطلاعات پایه شرکت خود را وارد کنید.</p>

        <form method="post" action="/company" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

            <div class="form-group">
                <label for="name">نام شرکت</label>
                <input class="form-control" type="text" id="name" name="name" value="<?= View::e($old['name'] ?? '') ?>" required>
                <?php foreach ($errors['name'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="registration_number">شماره ثبت شرکت</label>
                <input class="form-control ltr" type="text" id="registration_number" name="registration_number" value="<?= View::e($old['registration_number'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="industry">صنعت / حوزه فعالیت</label>
                <input class="form-control" type="text" id="industry" name="industry" value="<?= View::e($old['industry'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="country">کشور</label>
                <input class="form-control" type="text" id="country" name="country" value="<?= View::e($old['country'] ?? 'ایران') ?>">
            </div>

            <div class="form-group">
                <label for="city">شهر</label>
                <input class="form-control" type="text" id="city" name="city" value="<?= View::e($old['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="website">وب‌سایت</label>
                <input class="form-control ltr" type="text" id="website" name="website" value="<?= View::e($old['website'] ?? '') ?>" placeholder="https://example.com">
            </div>

            <div class="form-group">
                <label for="description">توضیحات کوتاه</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= View::e($old['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>کدهای کالایی (HS Codes)</label>
                <?php require __DIR__ . '/_hs-tree.php'; ?>
            </div>

            <button type="submit" class="btn">ثبت پروفایل شرکت</button>
        </form>
    </div>
</div>
