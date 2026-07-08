<?php
/** @var array $application */
/** @var array|null $company */
/** @var array|null $cohort */
/** @var array $scoreRows */
/** @var float|null $average */
/** @var bool $hasVariance */
/** @var array $reviewers */
/** @var array $clarifications */
/** @var array $documents */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;

$statusLabels = [
    'DRAFT' => 'پیش‌نویس', 'SUBMITTED' => 'ارسال‌شده', 'UNDER_REVIEW' => 'در حال بررسی',
    'ACCEPTED' => 'پذیرفته‌شده', 'REJECTED' => 'رد‌شده', 'PENDING_INFO' => 'نیازمند شفاف‌سازی', 'WITHDRAWN' => 'انصراف',
];
$reviewStatusLabels = ['ASSIGNED' => 'تخصیص‌یافته', 'DRAFT' => 'پیش‌نویس', 'SUBMITTED' => 'ثبت‌شده'];
$status = Session::flash('status');
$error = Session::flash('error');
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="dashboard-card">
            <h1><?= View::e($company['name'] ?? '-') ?></h1>
            <span class="badge"><?= View::e($statusLabels[$application['status']] ?? $application['status']) ?></span>
        </div>
        <p class="help-text">کوهورت: <?= View::e($cohort['name'] ?? '-') ?></p>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">تخصیص داور</h2>
        <form method="post" action="/admin/reviews/<?= View::e($application['id']) ?>/assign">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <select class="form-control" name="reviewer_user_id" required>
                    <option value="">-- انتخاب داور --</option>
                    <?php foreach ($reviewers as $r): ?>
                        <?php if (!$r['is_assigned']): ?>
                            <option value="<?= View::e($r['id']) ?>"><?= View::e($r['first_name'] . ' ' . $r['last_name']) ?> (بار کاری: <?= View::e((string) $r['active_workload']) ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn" style="width:auto">تخصیص داور</button>
        </form>

        <h3 style="font-size:0.95rem;margin-top:24px">داوران تخصیص‌یافته</h3>
        <?php if ($scoreRows === []): ?>
            <p class="help-text">هنوز داوری تخصیص نیافته است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>داور</th><th>وضعیت</th><th>امتیاز</th><th>تعارض منافع</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($scoreRows as $row): ?>
                        <tr>
                            <td data-label="داور"><?= View::e(($row['reviewer']['first_name'] ?? '') . ' ' . ($row['reviewer']['last_name'] ?? '')) ?></td>
                            <td data-label="وضعیت"><span class="badge"><?= View::e($reviewStatusLabels[$row['status']] ?? $row['status']) ?></span></td>
                            <td data-label="امتیاز" class="ltr-num"><?= $row['score'] !== null ? View::e((string) $row['score']) : '-' ?></td>
                            <td data-label="تعارض منافع"><?= $row['conflict_of_interest'] ? 'دارد' : '-' ?></td>
                            <td data-label="">
                                <form method="post" action="/admin/reviews/<?= View::e($application['id']) ?>/unassign/<?= View::e($row['reviewer_user_id']) ?>">
                                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                    <button type="submit" class="btn btn-secondary" style="width:auto">لغو تخصیص</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:12px">میانگین امتیاز: <strong class="ltr-num"><?= $average !== null ? View::e(number_format($average, 1)) : '-' ?></strong></p>

            <?php if ($hasVariance): ?>
                <div class="alert alert-error">اختلاف قابل توجهی بین امتیازهای داوران وجود دارد. لطفاً بررسی و تصمیم نهایی را به‌صورت دستی ثبت کنید.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">اسناد شرکت</h2>
        <?php if ($documents === []): ?>
            <p class="help-text">هنوز سندی بارگذاری نشده است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>فایل</th><th>نسخه</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td data-label="فایل"><?= View::e($doc['file_name']) ?></td>
                            <td data-label="نسخه" class="ltr-num">v<?= View::e((string) $doc['version']) ?></td>
                            <td data-label=""><a href="<?= View::e($doc['download_url']) ?>">دانلود</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($clarifications !== []): ?>
        <div class="card" style="margin-top:16px">
            <h2 style="margin-top:0;font-size:1.05rem">درخواست‌های شفاف‌سازی</h2>
            <?php foreach ($clarifications as $c): ?>
                <div class="section-block"><div class="section-body">
                    <p><strong>سوال:</strong> <?= View::e($c['question']) ?></p>
                    <p><strong>پاسخ:</strong> <?= $c['response'] ? View::e($c['response']) : 'در انتظار پاسخ متقاضی' ?></p>
                </div></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">تصمیم نهایی (Manual Override)</h2>
        <form method="post" action="/admin/reviews/<?= View::e($application['id']) ?>/override">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="status">وضعیت جدید</label>
                <select class="form-control" id="status" name="status">
                    <?php foreach (['UNDER_REVIEW' => 'در حال بررسی', 'PENDING_INFO' => 'نیازمند شفاف‌سازی', 'ACCEPTED' => 'پذیرش', 'REJECTED' => 'رد'] as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reason">دلیل (برای Audit Log)</label>
                <textarea class="form-control" id="reason" name="reason" rows="2" required></textarea>
            </div>
            <button type="submit" class="btn">ثبت تصمیم نهایی</button>
        </form>
    </div>
</div>
