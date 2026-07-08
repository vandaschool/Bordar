<?php
/** @var string $secret */
/** @var string $uri */
/** @var array $errors */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container">
    <div class="card">
        <h1>فعال‌سازی احراز هویت دومرحله‌ای</h1>
        <p>برای حساب‌های مدیر، فعال‌سازی 2FA الزامی است. این کد را در اپلیکیشن Google Authenticator یا مشابه وارد کنید:</p>

        <div class="secret-box ltr"><?= View::e($secret) ?></div>
        <p class="help-text ltr" style="word-break:break-all"><?= View::e($uri) ?></p>

        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= View::e($e) ?></div><?php endforeach; ?>

        <form method="post" action="/2fa/setup" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="code">کد ۶ رقمی اپلیکیشن</label>
                <input class="form-control otp-code" type="text" inputmode="numeric" maxlength="6" id="code" name="code" required autofocus>
            </div>
            <button type="submit" class="btn">فعال‌سازی و ورود</button>
        </form>
    </div>
</div>
