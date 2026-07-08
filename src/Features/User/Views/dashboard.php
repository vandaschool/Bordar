<?php
/** @var array|null $auth */
/** @var array|null $role */
/** @var string $csrf */

use App\Core\View;
?>
<div class="container-wide">
    <div class="card dashboard-card">
        <div>
            <h1>خوش آمدید، <?= View::e(($auth['first_name'] ?? '') . ' ' . ($auth['last_name'] ?? '')) ?></h1>
            <p>نقش شما: <span class="badge"><?= View::e($role['name'] ?? '-') ?></span></p>
        </div>
        <form method="post" action="/logout">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <button type="submit" class="btn btn-secondary" style="width:auto">خروج</button>
        </form>
    </div>
</div>
