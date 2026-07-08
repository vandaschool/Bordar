<?php
/** @var array $mentors */

use App\Core\View;
?>
<div class="container-wide">
    <div class="card">
        <div class="dashboard-card">
            <h1>منتورها</h1>
            <a class="btn btn-secondary" style="width:auto" href="/mentors/my-sessions">جلسات من</a>
        </div>

        <?php if ($mentors === []): ?>
            <p class="help-text">در حال حاضر منتوری با زمان‌های در دسترس ثبت‌شده وجود ندارد.</p>
        <?php else: ?>
            <?php foreach ($mentors as $mentor): ?>
                <div class="section-block"><div class="section-body">
                    <div class="dashboard-card">
                        <strong><?= View::e(($mentor['user']['first_name'] ?? '') . ' ' . ($mentor['user']['last_name'] ?? '')) ?></strong>
                        <a class="btn" style="width:auto" href="/mentors/<?= View::e($mentor['id']) ?>/slots">مشاهده زمان‌ها</a>
                    </div>
                    <?php $expertise = json_decode($mentor['expertise'] ?? '[]', true) ?: []; ?>
                    <?php if ($expertise !== []): ?>
                        <p>
                            <?php foreach ($expertise as $tag): ?>
                                <span class="badge" style="margin-inline-end:4px"><?= View::e($tag) ?></span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($mentor['bio']): ?><p><?= View::e($mentor['bio']) ?></p><?php endif; ?>
                </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
