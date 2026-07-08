<?php
/** @var array $hsTree */
/** @var array $selected */

use App\Core\View;

$selected ??= [];
?>
<div class="hs-tree" data-hs-tree>
    <input type="text" class="form-control hs-search" data-hs-search placeholder="جستجوی کد یا عنوان کالا...">

    <?php foreach ($hsTree as $chapter): ?>
        <details data-hs-chapter open>
            <summary>
                <input type="checkbox" data-hs-chapter-toggle aria-label="انتخاب همه کدهای این فصل">
                <span class="hs-code ltr"><?= View::e($chapter['code']) ?></span>
                <span><?= View::e($chapter['title']) ?></span>
            </summary>
            <?php foreach ($chapter['children'] as $heading): ?>
                <label class="hs-item" data-hs-item>
                    <input type="checkbox" name="hs_codes[]" value="<?= View::e($heading['code']) ?>" data-hs-child
                        <?= in_array($heading['code'], $selected, true) ? 'checked' : '' ?>>
                    <span class="hs-code ltr"><?= View::e($heading['code']) ?></span>
                    <span><?= View::e($heading['title']) ?></span>
                </label>
            <?php endforeach; ?>
        </details>
    <?php endforeach; ?>
</div>
<script src="/assets/js/hs-tree.js" defer></script>
