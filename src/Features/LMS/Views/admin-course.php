<?php
/** @var array $course */
/** @var array $lessons */
/** @var string|null $status */
/** @var string|null $error */
/** @var string $csrf */

use App\Core\View;

$typeLabels = ['VIDEO' => 'ویدیو', 'TEXT' => 'متنی', 'QUIZ' => 'آزمون', 'EXTERNAL_LINK' => 'لینک خارجی'];
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <h1><?= View::e($course['title']) ?></h1>
        <p class="help-text"><?= View::e($course['description'] ?? '') ?></p>

        <h2 style="font-size:1rem">افزودن درس جدید</h2>
        <form method="post" action="/admin/lms/<?= View::e($course['id']) ?>/lessons">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="title">عنوان درس</label>
                <input class="form-control" type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="description">توضیح کوتاه</label>
                <input class="form-control" type="text" id="description" name="description">
            </div>
            <div class="form-group">
                <label for="type">نوع محتوا</label>
                <select class="form-control" id="type" name="type">
                    <?php foreach ($typeLabels as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="body">متن محتوا (برای نوع متنی/آزمون)</label>
                <textarea class="form-control" id="body" name="body" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="url">لینک (برای نوع ویدیو/لینک خارجی)</label>
                <input class="form-control ltr" type="text" id="url" name="url" placeholder="https://...">
            </div>
            <button type="submit" class="btn" style="width:auto">افزودن درس</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1rem">دروس</h2>
        <?php if ($lessons === []): ?>
            <p class="help-text">هنوز درسی اضافه نشده است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>ترتیب</th><th>عنوان</th><th>نوع</th></tr></thead>
                <tbody>
                    <?php foreach ($lessons as $l): ?>
                        <tr>
                            <td data-label="ترتیب" class="ltr-num"><?= View::e((string) $l['order']) ?></td>
                            <td data-label="عنوان"><?= View::e($l['title']) ?></td>
                            <td data-label="نوع"><?= View::e($typeLabels[$l['type']] ?? $l['type']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
