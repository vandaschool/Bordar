<?php
/** @var array $notifications */

use App\Core\View;
use App\Lib\DateConverter;
?>
<div class="container-wide">
    <div class="card">
        <h1>اعلان‌ها</h1>

        <?php if ($notifications === []): ?>
            <p class="help-text">اعلانی برای نمایش وجود ندارد.</p>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="section-block"><div class="section-body">
                    <div class="dashboard-card">
                        <strong><?= View::e($n['title']) ?></strong>
                        <span class="help-text ltr-num" style="margin:0"><?= View::e(DateConverter::relativeJalali($n['created_at'])) ?></span>
                    </div>
                    <p style="margin-bottom:0"><?= View::e($n['message']) ?></p>
                    <?php if ($n['link']): ?><a href="<?= View::e($n['link']) ?>">مشاهده</a><?php endif; ?>
                </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
