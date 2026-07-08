<?php

declare(strict_types=1);

namespace App\Features\Task\Services;

use App\Core\AuditLog;
use App\Features\Task\Models\Task;

final class TaskService
{
    /** @param array<string, mixed> $data */
    public function create(string $companyId, array $data): array
    {
        $id = Task::insert([
            'title' => $data['title'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'company_id' => $companyId,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'priority' => in_array($data['priority'] ?? 'MEDIUM', ['LOW', 'MEDIUM', 'HIGH'], true) ? $data['priority'] : 'MEDIUM',
            'status' => 'TODO',
        ]);

        AuditLog::record('task.created', 'Task', $id, null, ['title' => $data['title']]);

        return Task::find($id);
    }

    public function updateStatus(string $taskId, string $companyId, string $newStatus): array
    {
        if (!in_array($newStatus, Task::STATUSES, true)) {
            throw new \InvalidArgumentException('وضعیت نامعتبر است.');
        }

        $task = Task::findScoped($taskId, $companyId);

        if ($task === null) {
            throw new \RuntimeException('وظیفه یافت نشد.');
        }

        Task::update($taskId, ['status' => $newStatus]);
        AuditLog::record('task.status_changed', 'Task', $taskId, ['status' => $task['status']], ['status' => $newStatus]);

        return Task::find($taskId);
    }

    public function delete(string $taskId, string $companyId): void
    {
        $task = Task::findScoped($taskId, $companyId);

        if ($task === null) {
            throw new \RuntimeException('وظیفه یافت نشد.');
        }

        Task::softDelete($taskId);
        AuditLog::record('task.deleted', 'Task', $taskId);
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function board(string $companyId): array
    {
        $tasks = Task::forCompany($companyId);
        $board = array_fill_keys(Task::STATUSES, []);

        foreach ($tasks as $task) {
            $board[$task['status']][] = $task;
        }

        return $board;
    }
}
