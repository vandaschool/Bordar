<?php
/** @var array $invoice */
/** @var array|null $cohort */
/** @var array $plan */
/** @var bool $isFullyPaid */
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
        <h1>پرداخت شهریه برنامه</h1>
        <p class="help-text">کوهورت: <?= View::e($cohort['name'] ?? '-') ?> — مهلت پرداخت: <span class="ltr-num"><?= View::e(DateConverter::toJalaliDate($invoice['due_date'])) ?></span></p>
        <p>مبلغ کل: <strong class="ltr-num"><?= number_format((float) $invoice['amount']) ?></strong> ریال در <span class="ltr-num"><?= View::e((string) $invoice['installment_count']) ?></span> قسط</p>

        <?php if ($isFullyPaid): ?>
            <div class="alert alert-success">شهریه به‌طور کامل پرداخت شده است. <a href="/onboarding">شروع فرآیند Onboarding</a></div>
        <?php endif; ?>
    </div>

    <?php foreach ($plan as $installment): ?>
        <div class="card" style="margin-top:16px">
            <div class="dashboard-card">
                <h2 style="margin:0;font-size:1.05rem">قسط <span class="ltr-num"><?= View::e((string) $installment['number']) ?></span></h2>
                <span class="badge"><?= $installment['status'] === 'PAID' ? 'پرداخت‌شده' : 'در انتظار پرداخت' ?></span>
            </div>
            <p class="ltr-num">مبلغ: <?= number_format($installment['amount']) ?> ریال</p>

            <?php if ($installment['status'] !== 'PAID'): ?>
                <div class="cta-row" style="justify-content:flex-start;margin-bottom:16px">
                    <form method="post" action="/payment/<?= $installment['number'] ?>/zarinpal">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <button type="submit" class="btn">پرداخت آنلاین (زرین‌پال)</button>
                    </form>
                </div>

                <details>
                    <summary style="cursor:pointer">ثبت فیش واریز بانکی</summary>
                    <form method="post" action="/payment/<?= $installment['number'] ?>/manual" enctype="multipart/form-data" style="margin-top:12px">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <div class="form-group">
                            <label for="tracking_number-<?= $installment['number'] ?>">شماره پیگیری</label>
                            <input class="form-control ltr" type="text" id="tracking_number-<?= $installment['number'] ?>" name="tracking_number" required>
                        </div>
                        <div class="form-group">
                            <label for="transfer_date-<?= $installment['number'] ?>">تاریخ واریز (شمسی)</label>
                            <input class="form-control ltr" type="text" id="transfer_date-<?= $installment['number'] ?>" name="transfer_date" placeholder="1405/04/17" required>
                        </div>
                        <div class="form-group">
                            <label for="receipt-<?= $installment['number'] ?>">تصویر فیش (JPG/PNG/PDF)</label>
                            <input class="form-control" type="file" id="receipt-<?= $installment['number'] ?>" name="receipt" required>
                        </div>
                        <button type="submit" class="btn btn-secondary">ثبت فیش واریزی</button>
                    </form>
                </details>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
