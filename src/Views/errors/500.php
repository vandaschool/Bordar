<?php
/** @var string $message */
/** @var Throwable|null $debug */

use App\Core\View;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>خطا</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="container">
    <div class="card" style="text-align:center">
        <h1>خطایی رخ داد</h1>
        <p><?= View::e($message) ?></p>
        <?php if ($debug !== null): ?>
            <pre style="text-align:left;direction:ltr;overflow:auto;background:#f3f4f6;padding:12px;border-radius:8px;"><?= View::e($debug->getTraceAsString()) ?></pre>
        <?php endif; ?>
        <a class="btn" href="/">بازگشت به صفحه اصلی</a>
    </div>
</div>
</body>
</html>
