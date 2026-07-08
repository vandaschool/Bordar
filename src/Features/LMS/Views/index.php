<?php
/** @var array $courses */
/** @var bool $hasCohort */

use App\Core\View;
?>
<div class="container-wide">
    <div class="card">
        <h1>مرکز آموزش</h1>

        <?php if (!$hasCohort): ?>
            <p class="help-text">پس از پذیرش نهایی در یک کوهورت، دوره‌های آموزشی مربوطه اینجا نمایش داده می‌شوند.</p>
        <?php elseif ($courses === []): ?>
            <p class="help-text">هنوز دوره‌ای برای کوهورت شما تعریف نشده است.</p>
        <?php else: ?>
            <?php foreach ($courses as $c): ?>
                <div class="section-block"><div class="section-body">
                    <div class="dashboard-card">
                        <strong><?= View::e($c['title']) ?></strong>
                        <a class="btn" style="width:auto" href="/lms/<?= View::e($c['id']) ?>">مشاهده (<span class="ltr-num"><?= $c['lesson_count'] ?></span> درس)</a>
                    </div>
                    <p class="help-text" style="margin-bottom:0"><?= View::e($c['description'] ?? '') ?></p>
                </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
