<?php
/** @var array $company */
/** @var array $checklist */
/** @var int $progress */
/** @var bool $showTour */
/** @var string $csrf */

use App\Core\View;

$tourSteps = [
    ['title' => 'به بردار خوش آمدید 👋', 'text' => 'در چند قدم کوتاه، محیط پلتفرم را با هم مرور می‌کنیم.'],
    ['title' => 'داشبورد شما', 'text' => 'از داشبورد به پروفایل شرکت، اسناد، پرداخت و درخواست‌ها دسترسی دارید.'],
    ['title' => 'چک‌لیست هفته اول', 'text' => 'در همین صفحه، چک‌لیست هفته اول شما برای شروع سریع‌تر آماده است.'],
    ['title' => 'شروع کنید!', 'text' => 'هر زمان سوالی داشتید، از بخش پشتیبانی با ما در تماس باشید.'],
];
?>
<div class="container-wide">
    <div class="card">
        <h1>خوش آمدید به بردار، <?= View::e($company['name']) ?></h1>
        <p class="help-text">این صفحه راهنمای شروع کار شما در هفته اول برنامه است.</p>

        <div class="progress-bar"><div class="progress-bar-fill" style="width:<?= (int) $progress ?>%"></div></div>
        <p class="help-text ltr-num" style="text-align:left"><?= (int) $progress ?>%</p>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">چک‌لیست هفته اول</h2>

        <?php foreach ($checklist as $item): ?>
            <div class="checklist-item <?= $item['completed'] ? 'done' : '' ?>">
                <?php if ($item['auto']): ?>
                    <span><?= $item['completed'] ? '✅' : '⬜️' ?></span>
                    <span class="checklist-label"><?= View::e($item['label']) ?></span>
                <?php else: ?>
                    <form method="post" action="/onboarding/checklist" style="display:contents">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <input type="hidden" name="item_key" value="<?= View::e($item['key']) ?>">
                        <input type="hidden" name="completed" value="<?= $item['completed'] ? '0' : '1' ?>">
                        <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.1rem;padding:0">
                            <?= $item['completed'] ? '✅' : '⬜️' ?>
                        </button>
                        <span class="checklist-label"><?= View::e($item['label']) ?></span>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($showTour): ?>
    <div data-guided-tour data-steps="<?= View::e(json_encode($tourSteps, JSON_UNESCAPED_UNICODE)) ?>" data-complete-url="/onboarding/tour/complete" data-csrf="<?= View::e($csrf) ?>"></div>
    <script src="/assets/js/guided-tour.js" defer></script>
<?php endif; ?>
