<?php
/** @var array $application */
/** @var array|null $cohort */
/** @var array $data */
/** @var array $missing */
/** @var bool $readOnly */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;

$statusLabels = [
    'DRAFT' => 'پیش‌نویس',
    'SUBMITTED' => 'ارسال‌شده',
    'UNDER_REVIEW' => 'در حال بررسی',
    'ACCEPTED' => 'پذیرفته‌شده',
    'REJECTED' => 'رد‌شده',
    'PENDING_INFO' => 'نیازمند تکمیل اطلاعات',
    'WITHDRAWN' => 'انصراف داده‌شده',
];

$missingLabels = [
    'business_overview' => 'معرفی کسب‌وکار',
    'export_experience' => 'سابقه صادراتی',
    'target_markets' => 'بازارهای هدف',
    'team_size' => 'اندازه تیم',
    'goals' => 'اهداف از شتاب‌دهنده',
];

$status = Session::flash('status');
$error = Session::flash('error');
$field = static fn (string $name) => View::e($data[$name] ?? '');
$dis = $readOnly ? 'disabled' : '';
?>
<div class="container-wide">
    <div class="card">
        <div class="dashboard-card">
            <h1>درخواست پذیرش — <?= View::e($cohort['name'] ?? '') ?></h1>
            <span class="badge"><?= View::e($statusLabels[$application['status']] ?? $application['status']) ?></span>
        </div>

        <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

        <?php if ($readOnly): ?>
            <p class="help-text">این درخواست ارسال شده و دیگر قابل ویرایش نیست.</p>
        <?php endif; ?>

        <form data-autosave-form data-autosave-url="/applications/<?= View::e($application['id']) ?>/autosave" data-csrf="<?= View::e($csrf) ?>" data-required-labels="<?= View::e(json_encode($missingLabels, JSON_UNESCAPED_UNICODE)) ?>">
            <details class="section-block" open>
                <summary>۱. معرفی کسب‌وکار</summary>
                <div class="section-body">
                    <div class="form-group">
                        <label for="business_overview">کسب‌وکار خود را معرفی کنید</label>
                        <textarea class="form-control" id="business_overview" name="business_overview" rows="4" data-field <?= $dis ?>><?= $field('business_overview') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="export_experience">سابقه صادراتی شما چیست؟</label>
                        <textarea class="form-control" id="export_experience" name="export_experience" rows="3" data-field <?= $dis ?>><?= $field('export_experience') ?></textarea>
                    </div>
                </div>
            </details>

            <details class="section-block">
                <summary>۲. بازار هدف و وضعیت مالی</summary>
                <div class="section-body">
                    <div class="form-group">
                        <label for="target_markets">بازارهای هدف صادراتی</label>
                        <input class="form-control" type="text" id="target_markets" name="target_markets" value="<?= $field('target_markets') ?>" data-field <?= $dis ?>>
                    </div>
                    <div class="form-group">
                        <label for="annual_revenue">گردش مالی سالانه (اختیاری)</label>
                        <input class="form-control ltr" type="text" id="annual_revenue" name="annual_revenue" value="<?= $field('annual_revenue') ?>" data-field <?= $dis ?>>
                    </div>
                </div>
            </details>

            <details class="section-block">
                <summary>۳. تیم</summary>
                <div class="section-body">
                    <div class="form-group">
                        <label for="team_size">تعداد اعضای تیم</label>
                        <input class="form-control ltr" type="text" id="team_size" name="team_size" value="<?= $field('team_size') ?>" data-field <?= $dis ?>>
                    </div>
                    <div class="form-group">
                        <label for="key_team_members">اعضای کلیدی و نقش آن‌ها (اختیاری)</label>
                        <textarea class="form-control" id="key_team_members" name="key_team_members" rows="3" data-field <?= $dis ?>><?= $field('key_team_members') ?></textarea>
                    </div>
                </div>
            </details>

            <details class="section-block">
                <summary>۴. اهداف و نیازها</summary>
                <div class="section-body">
                    <div class="form-group">
                        <label for="goals">اهداف شما از حضور در شتاب‌دهنده</label>
                        <textarea class="form-control" id="goals" name="goals" rows="3" data-field <?= $dis ?>><?= $field('goals') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="support_needed">چه نوع حمایتی نیاز دارید؟ (اختیاری)</label>
                        <textarea class="form-control" id="support_needed" name="support_needed" rows="3" data-field <?= $dis ?>><?= $field('support_needed') ?></textarea>
                    </div>
                </div>
            </details>

            <?php if (!$readOnly): ?>
                <p class="autosave-status" data-autosave-status>تغییرات به‌صورت خودکار ذخیره می‌شوند.</p>
            <?php endif; ?>
        </form>

        <?php if (!$readOnly): ?>
            <div class="alert alert-error" style="margin-top:18px" data-missing-hint <?= $missing === [] ? 'hidden' : '' ?>>
                برای ارسال درخواست، این بخش‌ها را تکمیل کنید:
                <span data-missing-list><?= View::e(implode('، ', array_map(static fn ($f) => $missingLabels[$f] ?? $f, $missing))) ?></span>
            </div>
            <form method="post" action="/applications/<?= View::e($application['id']) ?>/submit" style="margin-top:18px" data-submit-form <?= $missing === [] ? '' : 'hidden' ?>>
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <button type="submit" class="btn">ارسال نهایی درخواست</button>
            </form>
        <?php endif; ?>

        <script src="/assets/js/application-autosave.js" defer></script>
    </div>
</div>
