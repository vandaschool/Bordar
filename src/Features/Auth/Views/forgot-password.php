<?php
/** @var array $errors */
/** @var string|null $status */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container">
    <div class="card">
        <h1>فراموشی رمز عبور</h1>
        <p>ایمیل حساب کاربری خود را وارد کنید تا کد بازیابی برایتان ارسال شود.</p>

        <form method="post" action="/forgot-password" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="email">ایمیل</label>
                <input class="form-control" type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn">ارسال کد بازیابی</button>
        </form>
        <p class="help-text"><a href="/login">بازگشت به ورود</a></p>
    </div>
</div>
