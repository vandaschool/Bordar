<?php
/** @var string $email */
/** @var array $errors */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;

$status = Session::flash('status');
$debugOtp = Session::flash('debug_otp');
?>
<div class="container">
    <div class="card">
        <h1>تایید ایمیل</h1>
        <p>کد ۶ رقمی ارسال‌شده به <strong class="ltr"><?= View::e($email) ?></strong> را وارد کنید.</p>

        <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
        <?php if ($debugOtp): ?><div class="alert alert-success">کد آزمایشی (فقط در محیط توسعه): <strong class="ltr"><?= View::e($debugOtp) ?></strong></div><?php endif; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= View::e($e) ?></div><?php endforeach; ?>

        <form method="post" action="/verify-email" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="code">کد تایید</label>
                <input class="form-control otp-code" type="text" inputmode="numeric" maxlength="6" id="code" name="code" required autofocus>
            </div>
            <button type="submit" class="btn">تایید</button>
        </form>

        <form method="post" action="/verify-email/resend" style="margin-top:10px">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <button type="submit" class="btn btn-secondary">ارسال مجدد کد</button>
        </form>
    </div>
</div>
