<?php
/** @var array $board */
/** @var string|null $status */
/** @var string|null $error */
/** @var string $csrf */

use App\Core\View;
use App\Lib\DateConverter;

$columnLabels = ['TODO' => 'انجام‌نشده', 'IN_PROGRESS' => 'در حال انجام', 'DONE' => 'انجام‌شده', 'BLOCKED' => 'مسدود‌شده'];
?>
<div class="container-wide">
    <?php if ($status): ?><div class="alert alert-success"><?= View::e($status) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <div class="card">
        <h1>مدیریت وظایف</h1>

        <details>
            <summary style="cursor:pointer">+ وظیفه جدید</summary>
            <form method="post" action="/tasks" style="margin-top:12px">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="form-group">
                    <label for="title">عنوان</label>
                    <input class="form-control" type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">توضیحات</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="priority">اولویت</label>
                    <select class="form-control" id="priority" name="priority">
                        <option value="LOW">کم</option>
                        <option value="MEDIUM" selected>متوسط</option>
                        <option value="HIGH">زیاد</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="due_date">مهلت انجام (شمسی، اختیاری)</label>
                    <input class="form-control ltr" type="text" id="due_date" name="due_date" placeholder="1405/05/01">
                </div>
                <button type="submit" class="btn" style="width:auto">افزودن وظیفه</button>
            </form>
        </details>
    </div>

    <div class="board-tabs">
        <?php foreach ($columnLabels as $key => $label): ?>
            <button type="button" class="step-pill <?= $key === 'TODO' ? 'active' : '' ?>" data-board-tab="<?= $key ?>"><?= $label ?></button>
        <?php endforeach; ?>
    </div>

    <div class="kanban-board" data-task-board data-csrf="<?= View::e($csrf) ?>" style="margin-top:16px">
        <?php foreach ($columnLabels as $key => $label): ?>
            <div class="kanban-column <?= $key === 'TODO' ? 'active' : '' ?>" data-board-column="<?= $key ?>">
                <h3><?= $label ?> <span class="ltr-num"><?= count($board[$key]) ?></span></h3>
                <div class="kanban-column-body" data-column-body>
                    <?php foreach ($board[$key] as $task): ?>
                        <div class="task-card" draggable="true" data-task-card data-task-id="<?= View::e($task['id']) ?>">
                            <span class="priority-tag <?= View::e($task['priority']) ?>"><?= ['LOW' => 'کم', 'MEDIUM' => 'متوسط', 'HIGH' => 'زیاد'][$task['priority']] ?></span>
                            <div><?= View::e($task['title']) ?></div>
                            <?php if ($task['due_date']): ?>
                                <div class="help-text ltr-num" style="margin:4px 0 0"><?= View::e(DateConverter::toJalaliDate($task['due_date'])) ?></div>
                            <?php endif; ?>
                            <form method="post" action="/tasks/<?= View::e($task['id']) ?>/delete" style="margin-top:6px">
                                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                <button type="submit" class="btn btn-secondary" style="width:auto;padding:2px 10px;font-size:0.78rem">حذف</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="/assets/js/task-board.js" defer></script>
