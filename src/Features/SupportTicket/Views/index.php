<?php
/** @var array $tickets */
/** @var array $categories */
/** @var string|null $status */
/** @var string|null $error */
/** @var string $csrf */

use App\Core\View;

$statusLabels = ['OPEN' => 'باز', 'IN_PROGRESS' => 'در حال بررسی', 'RESOLVED' => 'حل‌شده', 'CLOSED' => 'بسته‌شده', 'ESCALATED' => 'ارجاع‌شده', 'PENDING_USER_RESPONSE' => 'در انتظار پاسخ شما'];
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <h1>پشتیبانی</h1>
        <details>
            <summary style="cursor:pointer">+ ثبت تیکت جدید</summary>
            <form method="post" action="/tickets" style="margin-top:12px">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="form-group">
                    <label for="subject">موضوع</label>
                    <input class="form-control" type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="category">دسته‌بندی</label>
                    <select class="form-control" id="category" name="category">
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">توضیحات</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn" style="width:auto">ثبت تیکت</button>
            </form>
        </details>
    </div>

    <div class="card" style="margin-top:16px">
        <?php if ($tickets === []): ?>
            <p class="help-text">هنوز تیکتی ثبت نکرده‌اید.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>موضوع</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td data-label="موضوع"><?= View::e($t['subject']) ?></td>
                            <td data-label="وضعیت"><span class="badge"><?= View::e($statusLabels[$t['status']] ?? $t['status']) ?></span></td>
                            <td data-label=""><a href="/tickets/<?= View::e($t['id']) ?>">مشاهده</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
