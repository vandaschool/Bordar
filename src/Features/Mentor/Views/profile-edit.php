<?php
/** @var array|null $mentor */
/** @var array $availability */
/** @var array $expertise */
/** @var string|null $status */
/** @var string $csrf */

use App\Core\View;

$dayLabels = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
if ($availability === []) {
    $availability = [['day' => 6, 'start' => '09:00', 'end' => '13:00']];
}
?>
<div class="container">
    <div class="card">
        <h1>پروفایل و زمان‌های در دسترس منتور</h1>
        <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>

        <form method="post" action="/mentor/profile">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

            <div class="form-group">
                <label for="bio">درباره من</label>
                <textarea class="form-control" id="bio" name="bio" rows="3"><?= View::e($mentor['bio'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="expertise">حوزه‌های تخصصی (با کاما جدا کنید)</label>
                <input class="form-control" type="text" id="expertise" name="expertise" value="<?= View::e(implode('، ', $expertise)) ?>">
            </div>

            <div class="form-group">
                <label for="session_length_minutes">مدت هر جلسه (دقیقه)</label>
                <input class="form-control ltr" type="number" id="session_length_minutes" name="session_length_minutes" min="15" max="180" step="15" value="<?= View::e((string) ($mentor['session_length_minutes'] ?? 45)) ?>">
            </div>

            <h3 style="font-size:0.95rem">زمان‌های هفتگی در دسترس (به وقت محلی شما)</h3>
            <div id="availability-rows">
                <?php foreach ($availability as $rule): ?>
                    <div class="cta-row" style="justify-content:flex-start;margin-bottom:8px">
                        <select class="form-control" name="day[]" style="width:auto">
                            <?php foreach ($dayLabels as $i => $label): ?>
                                <option value="<?= $i ?>" <?= (int) $rule['day'] === $i ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input class="form-control ltr" type="time" name="start[]" value="<?= View::e($rule['start']) ?>" style="width:auto">
                        <input class="form-control ltr" type="time" name="end[]" value="<?= View::e($rule['end']) ?>" style="width:auto">
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="help-text">برای افزودن بازه بیشتر، سطرهای فرم را در بازدید بعدی تکرار می‌کنیم؛ فعلاً یک یا چند بازه بالا کافی است.</p>

            <button type="submit" class="btn">ذخیره</button>
        </form>
    </div>
</div>
