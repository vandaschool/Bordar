<?php
/** @var string $content */
/** @var array|null $auth */

use App\Config\App;
use App\Core\View;
use App\Features\Notification\Models\Notification;

$unreadCount = !empty($auth) ? Notification::unreadCount($auth['id']) : 0;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= View::e(App::name()) ?> | شتاب‌دهنده آنلاین</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a href="/" class="brand"><?= View::e(App::name()) ?></a>
    <nav>
        <?php if (!empty($auth)): ?>
            <a href="/notifications">اعلان‌ها<?= $unreadCount > 0 ? ' <span class="badge">' . $unreadCount . '</span>' : '' ?></a>
            <a href="/dashboard">داشبورد</a>
        <?php else: ?>
            <a href="/login">ورود</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <?= $content ?>
</main>
</body>
</html>
