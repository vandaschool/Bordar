<?php
/** @var array $mentor */
/** @var array|null $mentorUser */
/** @var array $slotsByDay */
/** @var string $viewerTz */
/** @var string|null $error */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container-wide">
    <div class="card">
        <h1>رزرو جلسه با <?= View::e(($mentorUser['first_name'] ?? '') . ' ' . ($mentorUser['last_name'] ?? '')) ?></h1>
        <p class="help-text">زمان‌ها بر اساس منطقه زمانی شما (<span class="ltr"><?= View::e($viewerTz) ?></span>) نمایش داده می‌شوند.</p>

        <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

        <?php if ($slotsByDay === []): ?>
            <p class="help-text">در حال حاضر زمان خالی برای این منتور وجود ندارد.</p>
        <?php else: ?>
            <?php foreach ($slotsByDay as $day => $slots): ?>
                <div class="section-block" open>
                    <div class="section-body">
                        <strong class="ltr-num"><?= View::e($day) ?></strong>
                        <div class="slot-grid" style="margin-top:10px">
                            <?php foreach ($slots as $slot): ?>
                                <button type="button" class="slot-btn" data-start="<?= View::e($slot['start_utc']) ?>" data-end="<?= View::e($slot['end_utc']) ?>" data-label="<?= View::e($slot['label']) ?>">
                                    <?= View::e($slot['label']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <form method="post" action="/mentors/<?= View::e($mentor['id']) ?>/book" id="book-form" style="margin-top:16px" hidden>
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <input type="hidden" name="start_utc" id="book-start">
                <input type="hidden" name="end_utc" id="book-end">
                <p>زمان انتخاب‌شده: <strong class="ltr-num" id="selected-label"></strong></p>
                <div class="form-group">
                    <label for="agenda">موضوع جلسه</label>
                    <textarea class="form-control" id="agenda" name="agenda" rows="3" placeholder="مثلاً: مشاوره در مورد استراتژی قیمت‌گذاری صادراتی"></textarea>
                </div>
                <button type="submit" class="btn">تایید و رزرو جلسه</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/js/mentor-slots.js" defer></script>
