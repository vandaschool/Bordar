<?php
/** @var array $errors */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container">
    <div class="card">
        <h1>تایید دومرحله‌ای</h1>
        <p>کد ۶ رقمی اپلیکیشن احراز هویت خود را وارد کنید.</p>

        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= View::e($e) ?></div><?php endforeach; ?>

        <form method="post" action="/2fa/verify" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="code">کد تایید</label>
                <input class="form-control otp-code" type="text" inputmode="numeric" maxlength="6" id="code" name="code" required autofocus>
            </div>
            <button type="submit" class="btn">تایید</button>
        </form>
    </div>
</div>
