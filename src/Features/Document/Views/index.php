<?php
/** @var array $documents */
/** @var array $types */
/** @var string $csrf */

use App\Core\Session;
use App\Core\View;
use App\Lib\DateConverter;

$statusLabels = ['PENDING' => 'در انتظار بررسی', 'APPROVED' => 'تاییدشده', 'REJECTED' => 'ردشده'];
$status = Session::flash('status');
$error = Session::flash('error');
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <h1>مخزن اسناد شرکت</h1>
        <p class="help-text">فرمت‌های مجاز: PDF، JPG، PNG، DOCX، XLSX — حداکثر ۱۰ مگابایت.</p>

        <form method="post" action="/documents" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="form-group">
                <label for="document_type">نوع سند</label>
                <select class="form-control" id="document_type" name="document_type">
                    <?php foreach ($types as $value => $label): ?>
                        <option value="<?= View::e($value) ?>"><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="file">فایل</label>
                <input class="form-control" type="file" id="file" name="file" required>
            </div>
            <div class="form-group">
                <label for="expires_at">تاریخ انقضا (شمسی، اختیاری)</label>
                <input class="form-control ltr" type="text" id="expires_at" name="expires_at" placeholder="1405/06/01">
            </div>
            <button type="submit" class="btn">بارگذاری سند</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 style="margin-top:0;font-size:1.05rem">اسناد بارگذاری‌شده</h2>

        <?php if ($documents === []): ?>
            <p class="help-text">هنوز سندی بارگذاری نشده است.</p>
        <?php else: ?>
            <table class="table-list">
                <thead><tr><th>نام فایل</th><th>نوع</th><th>نسخه</th><th>وضعیت</th><th>انقضا</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td data-label="فایل"><?= View::e($doc['file_name']) ?></td>
                            <td data-label="نوع"><?= View::e($types[$doc['document_type']] ?? $doc['document_type']) ?></td>
                            <td data-label="نسخه" class="ltr-num">v<?= View::e((string) $doc['version']) ?></td>
                            <td data-label="وضعیت">
                                <span class="badge"><?= View::e($statusLabels[$doc['status']] ?? $doc['status']) ?></span>
                                <?php if ($doc['is_expired']): ?><span class="badge" style="background:#fef2f2;color:#dc2626">منقضی‌شده</span><?php endif; ?>
                            </td>
                            <td data-label="انقضا" class="ltr-num"><?= View::e(DateConverter::toJalaliDate($doc['expires_at']) ?: '-') ?></td>
                            <td data-label="">
                                <a href="<?= View::e($doc['download_url']) ?>">دانلود</a>
                                &nbsp;
                                <form method="post" action="/documents/<?= View::e($doc['id']) ?>/delete" style="display:inline">
                                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                    <button type="submit" class="btn btn-secondary" style="width:auto">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
