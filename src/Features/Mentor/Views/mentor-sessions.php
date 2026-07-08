<?php
/** @var array $sessions */
/** @var string $viewerTz */
/** @var string|null $status */
/** @var string|null $error */
/** @var string $csrf */

use App\Core\View;
use App\Lib\DateConverter;

$statusLabels = ['SCHEDULED' => 'برنامه‌ریزی‌شده', 'COMPLETED' => 'برگزارشده', 'CANCELED' => 'لغوشده', 'RESCHEDULED' => 'زمان‌بندی مجدد'];
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="dashboard-card">
            <h1>جلسات منتورینگ من</h1>
            <a class="btn btn-secondary" style="width:auto" href="/mentor/profile">ویرایش پروفایل</a>
        </div>

        <?php if ($sessions === []): ?>
            <p class="help-text">هنوز جلسه‌ای رزرو نشده است.</p>
        <?php else: ?>
            <?php foreach ($sessions as $s): ?>
                <div class="section-block"><div class="section-body">
                    <div class="dashboard-card">
                        <strong><?= View::e($s['company']['name'] ?? '-') ?></strong>
                        <span class="badge"><?= View::e($statusLabels[$s['status']] ?? $s['status']) ?></span>
                    </div>
                    <p class="ltr-num"><?= View::e(DateConverter::toJalali($s['start_time'], 'Y/m/d H:i', $viewerTz)) ?></p>
                    <?php if ($s['agenda']): ?><p><strong>موضوع:</strong> <?= View::e($s['agenda']) ?></p><?php endif; ?>

                    <?php if ($s['status'] === 'SCHEDULED'): ?>
                        <form method="post" action="/mentor/sessions/<?= View::e($s['id']) ?>/outcome" style="margin-top:10px">
                            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                            <div class="form-group">
                                <label for="summary-<?= $s['id'] ?>">خلاصه جلسه</label>
                                <textarea class="form-control" id="summary-<?= $s['id'] ?>" name="summary" rows="2" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="action_plan-<?= $s['id'] ?>">برنامه اقدام</label>
                                <textarea class="form-control" id="action_plan-<?= $s['id'] ?>" name="action_plan" rows="2" required></textarea>
                            </div>
                            <div class="cta-row" style="justify-content:flex-start">
                                <button type="submit" class="btn">ثبت خلاصه و برنامه اقدام</button>
                            </div>
                        </form>
                        <form method="post" action="/mentor/sessions/<?= View::e($s['id']) ?>/cancel" style="margin-top:8px">
                            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                            <button type="submit" class="btn btn-secondary">لغو جلسه</button>
                        </form>
                    <?php elseif ($s['status'] === 'COMPLETED'): ?>
                        <p><strong>خلاصه:</strong> <?= View::e($s['summary'] ?? '') ?></p>
                        <p><strong>برنامه اقدام:</strong> <?= View::e($s['action_plan'] ?? '') ?></p>
                    <?php endif; ?>
                </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
