<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Company\Models\Company;
use App\Features\Task\Models\Task;
use App\Features\Task\Services\TaskService;
use PDO;
use PHPUnit\Framework\TestCase;

final class TaskServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $roleId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        $role = Role::findByName('Applicant');
        self::$roleId = $role['id'] ?? Model::uuid();
        if ($role === null) {
            Role::insert(['id' => self::$roleId, 'name' => 'Applicant', 'description' => 'Applicant', 'permissions' => '[]']);
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['tasks', 'companies', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function makeCompany(string $suffix): array
    {
        $userId = User::insert([
            'email' => "owner-task-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'O', 'last_name' => $suffix,
            'role_id' => self::$roleId,
            'is_active' => 1,
        ]);
        $companyId = Company::insert(['name' => "Co {$suffix}", 'owner_user_id' => $userId, 'status' => 'ACTIVE', 'hs_codes' => '[]']);

        return Company::find($companyId);
    }

    public function test_new_task_starts_in_todo_column(): void
    {
        $company = $this->makeCompany('a');
        $service = new TaskService();

        $task = $service->create($company['id'], ['title' => 'Prepare pitch deck', 'description' => '', 'priority' => 'HIGH', 'due_date' => null]);

        $this->assertSame('TODO', $task['status']);
        $this->assertSame('HIGH', $task['priority']);
    }

    public function test_update_status_moves_task_between_board_columns(): void
    {
        $company = $this->makeCompany('b');
        $service = new TaskService();
        $task = $service->create($company['id'], ['title' => 'x', 'description' => '', 'priority' => 'MEDIUM', 'due_date' => null]);

        $service->updateStatus($task['id'], $company['id'], 'IN_PROGRESS');

        $this->assertSame('IN_PROGRESS', Task::find($task['id'])['status']);
    }

    public function test_update_status_rejects_invalid_status(): void
    {
        $company = $this->makeCompany('c');
        $service = new TaskService();
        $task = $service->create($company['id'], ['title' => 'x', 'description' => '', 'priority' => 'MEDIUM', 'due_date' => null]);

        $this->expectException(\InvalidArgumentException::class);
        $service->updateStatus($task['id'], $company['id'], 'NOT_A_STATUS');
    }

    public function test_cannot_update_status_of_another_companys_task(): void
    {
        $companyA = $this->makeCompany('d');
        $companyB = $this->makeCompany('e');
        $service = new TaskService();
        $task = $service->create($companyA['id'], ['title' => 'x', 'description' => '', 'priority' => 'MEDIUM', 'due_date' => null]);

        $this->expectException(\RuntimeException::class);
        $service->updateStatus($task['id'], $companyB['id'], 'DONE');
    }

    public function test_board_groups_tasks_by_status(): void
    {
        $company = $this->makeCompany('f');
        $service = new TaskService();
        $t1 = $service->create($company['id'], ['title' => 'a', 'description' => '', 'priority' => 'LOW', 'due_date' => null]);
        $t2 = $service->create($company['id'], ['title' => 'b', 'description' => '', 'priority' => 'LOW', 'due_date' => null]);
        $service->updateStatus($t2['id'], $company['id'], 'DONE');

        $board = $service->board($company['id']);

        $this->assertCount(1, $board['TODO']);
        $this->assertCount(1, $board['DONE']);
        $this->assertSame($t1['id'], $board['TODO'][0]['id']);
    }

    public function test_delete_soft_deletes_task(): void
    {
        $company = $this->makeCompany('g');
        $service = new TaskService();
        $task = $service->create($company['id'], ['title' => 'x', 'description' => '', 'priority' => 'MEDIUM', 'due_date' => null]);

        $service->delete($task['id'], $company['id']);

        $this->assertNull(Task::find($task['id']));
    }
}
