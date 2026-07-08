<?php
/** @var array $errors */
/** @var array $old */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container">
    <div class="card">
        <h1>ثبت‌نام</h1>
        <form method="post" action="/register" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

            <div class="form-group">
                <label for="first_name">نام</label>
                <input class="form-control" type="text" id="first_name" name="first_name" value="<?= View::e($old['first_name'] ?? '') ?>" required>
                <?php foreach ($errors['first_name'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="last_name">نام خانوادگی</label>
                <input class="form-control" type="text" id="last_name" name="last_name" value="<?= View::e($old['last_name'] ?? '') ?>" required>
                <?php foreach ($errors['last_name'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="email">ایمیل</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= View::e($old['email'] ?? '') ?>" required>
                <?php foreach ($errors['email'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="password">رمز عبور</label>
                <input class="form-control" type="password" id="password" name="password" required>
                <?php foreach ($errors['password'] ?? [] as $e): ?><div class="field-error"><?= View::e($e) ?></div><?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="password_confirmation">تکرار رمز عبور</label>
                <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn">ثبت‌نام</button>
        </form>
        <p class="help-text">قبلاً ثبت‌نام کرده‌اید؟ <a href="/login">ورود</a></p>
    </div>
</div>
