<?php
/** @var array $course */
/** @var array $lessons */

use App\Core\View;
?>
<div class="container-wide">
    <div class="card">
        <h1><?= View::e($course['title']) ?></h1>
        <p class="help-text"><?= View::e($course['description'] ?? '') ?></p>

        <?php if ($lessons === []): ?>
            <p class="help-text">هنوز درسی برای این دوره اضافه نشده است.</p>
        <?php else: ?>
            <?php foreach ($lessons as $lesson): ?>
                <?php $content = json_decode($lesson['content'], true) ?: []; ?>
                <details class="section-block">
                    <summary><?= View::e((string) $lesson['order']) ?>. <?= View::e($lesson['title']) ?></summary>
                    <div class="section-body">
                        <?php if ($lesson['description']): ?><p class="help-text"><?= View::e($lesson['description']) ?></p><?php endif; ?>

                        <?php if ($lesson['type'] === 'VIDEO'): ?>
                            <p><a href="<?= View::e($content['url'] ?? '#') ?>" target="_blank" rel="noopener">مشاهده ویدیو ↗</a></p>
                        <?php elseif ($lesson['type'] === 'EXTERNAL_LINK'): ?>
                            <p><a href="<?= View::e($content['url'] ?? '#') ?>" target="_blank" rel="noopener">مشاهده منبع خارجی ↗</a></p>
                        <?php else: ?>
                            <p><?= nl2br(View::e($content['body'] ?? '')) ?></p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
