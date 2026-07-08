<?php
/** @var array $payments */
/** @var string|null $status */
/** @var string|null $error */
/** @var string $csrf */

use App\Core\View;
use App\Lib\DateConverter;
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <h1>تایید فیش‌های واریزی</h1>

        <?php if ($payments === []): ?>
            <p class="help-text">فیش واریزی در انتظار تایید وجود ندارد.</p>
        <?php else: ?>
            <?php foreach ($payments as $p): ?>
                <div class="section-block"><div class="section-body">
                    <div class="dashboard-card">
                        <strong><?= View::e($p['company']['name'] ?? '-') ?></strong>
                        <span class="ltr-num">قسط <?= View::e((string) $p['installment_number']) ?> — <?= number_format((float) $p['amount']) ?> ریال</span>
                    </div>
                    <p>شماره پیگیری: <span class="ltr"><?= View::e($p['bank_tracking_number'] ?? '-') ?></span> —
                       تاریخ واریز: <span class="ltr-num"><?= View::e(DateConverter::toJalaliDate($p['bank_transfer_date']) ?: '-') ?></span></p>
                    <?php if ($p['receipt_image']): ?>
                        <p><a href="/admin/payments/<?= View::e($p['id']) ?>/receipt" target="_blank">مشاهده تصویر فیش</a></p>
                    <?php endif; ?>

                    <div class="cta-row" style="justify-content:flex-start">
                        <form method="post" action="/admin/payments/<?= View::e($p['id']) ?>/approve">
                            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                            <button type="submit" class="btn">تایید پرداخت</button>
                        </form>
                        <form method="post" action="/admin/payments/<?= View::e($p['id']) ?>/reject">
                            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                            <input type="hidden" name="reason" value="عدم تطابق اطلاعات فیش واریزی">
                            <button type="submit" class="btn btn-secondary">رد پرداخت</button>
                        </form>
                    </div>
                </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
