<?php
/** @var array $application */
/** @var array $assignment */
/** @var array|null $company */
/** @var array|null $cohort */
/** @var array $data */
/** @var array $clarifications */
/** @var array $documents */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;

$fieldLabels = [
    'business_overview' => 'معرفی کسب‌وکار',
    'export_experience' => 'سابقه صادراتی',
    'target_markets' => 'بازارهای هدف',
    'annual_revenue' => 'گردش مالی سالانه',
    'team_size' => 'اندازه تیم',
    'key_team_members' => 'اعضای کلیدی',
    'goals' => 'اهداف',
    'support_needed' => 'نیاز به حمایت',
];

$locked = $assignment['status'] === 'SUBMITTED';
$status = Session::flash('status');
$error = Session::flash('error');
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="dashboard-card">
            <h1><?= View::e($company['name'] ?? '-') ?></h1>
            <form method="post" action="/reviewer/applications/<?= View::e($application['id']) ?>/conflict">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <button type="submit" class="btn btn-secondary" style="width:auto">
                    <?= $assignment['conflict_of_interest'] ? 'رفع تعارض منافع' : 'اعلام تعارض منافع' ?>
                </button>
            </form>
        </div>
        <p class="help-text">کوهورت: <?= View::e($cohort['name'] ?? '-') ?></p>

        <?php foreach ($fieldLabels as $key => $label): ?>
            <?php if (!empty($data[$key])): ?>
                <div class="section-block"><div class="section-body">
                    <strong><?= View::e($label) ?></strong>
                    <p><?= nl2br(View::e($data[$key])) ?></p>
                </div></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">اسناد شرکت</h2>
        <?php if ($documents === []): ?>
            <p class="help-text">هنوز سندی بارگذاری نشده است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>فایل</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td data-label="فایل"><?= View::e($doc['file_name']) ?></td>
                            <td data-label=""><a href="<?= View::e($doc['download_url']) ?>">دانلود</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">امتیازدهی</h2>
        <?php if ($locked): ?>
            <p class="help-text">امتیازدهی شما ثبت و قفل شده است.</p>
            <p>امتیاز: <strong class="ltr-num"><?= View::e((string) $assignment['score']) ?></strong></p>
            <p><?= nl2br(View::e($assignment['feedback'] ?? '')) ?></p>
        <?php else: ?>
            <form method="post" action="/reviewer/applications/<?= View::e($application['id']) ?>/draft">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="form-group">
                    <label for="score">امتیاز (۰ تا ۱۰۰)</label>
                    <input class="form-control ltr" type="number" min="0" max="100" step="0.5" id="score" name="score" value="<?= View::e((string) ($assignment['score'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label for="feedback">بازخورد</label>
                    <textarea class="form-control" id="feedback" name="feedback" rows="4"><?= View::e($assignment['feedback'] ?? '') ?></textarea>
                </div>
                <div class="cta-row" style="justify-content:flex-start">
                    <button type="submit" class="btn btn-secondary">ذخیره پیش‌نویس</button>
                    <button type="submit" formaction="/reviewer/applications/<?= View::e($application['id']) ?>/submit" class="btn">ثبت نهایی امتیاز</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">درخواست شفاف‌سازی از متقاضی</h2>
        <?php foreach ($clarifications as $c): ?>
            <div class="section-block"><div class="section-body">
                <p><strong>سوال شما:</strong> <?= View::e($c['question']) ?></p>
                <p><strong>پاسخ متقاضی:</strong> <?= $c['response'] ? View::e($c['response']) : 'در انتظار پاسخ' ?></p>
            </div></div>
        <?php endforeach; ?>

        <form method="post" action="/reviewer/applications/<?= View::e($application['id']) ?>/clarify">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="question">سوال جدید</label>
                <textarea class="form-control" id="question" name="question" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-secondary">ارسال درخواست شفاف‌سازی</button>
        </form>
    </div>
</div>
