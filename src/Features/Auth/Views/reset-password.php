<?php
/** @var array $errors */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;

$debugOtp = Session::flash('debug_otp');
?>
<div class="container">
    <div class="card">
        <h1>تنظیم رمز عبور جدید</h1>
        <p>کد ارسال‌شده به ایمیل خود را همراه با رمز عبور جدید وارد کنید.</p>

        <?php if ($debugOtp): ?><div class="alert alert-success">کد آزمایشی (فقط در محیط توسعه): <strong class="ltr"><?= View::e($debugOtp) ?></strong></div><?php endif; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= View::e($e) ?></div><?php endforeach; ?>

        <form method="post" action="/reset-password" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="code">کد تایید</label>
                <input class="form-control otp-code" type="text" inputmode="numeric" maxlength="6" id="code" name="code" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">رمز عبور جدید</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirmation">تکرار رمز عبور جدید</label>
                <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn">تغییر رمز عبور</button>
        </form>
    </div>
</div>
