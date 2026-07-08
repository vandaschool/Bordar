<?php
/** @var array $errors */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;

$status = Session::flash('status');
?>
<div class="container">
    <div class="card">
        <h1>ورود</h1>

        <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
        <?php foreach ($errors as $e): if (!is_array($e)): ?><div class="alert alert-error"><?= View::e($e) ?></div><?php endif; endforeach; ?>

        <form method="post" action="/login" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

            <div class="form-group">
                <label for="email">ایمیل</label>
                <input class="form-control" type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">رمز عبور</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">ورود</button>
        </form>
        <p class="help-text">
            <a href="/forgot-password">فراموشی رمز عبور</a> ·
            حساب ندارید؟ <a href="/register">ثبت‌نام</a>
        </p>
    </div>
</div>
