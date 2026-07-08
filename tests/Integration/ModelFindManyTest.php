<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Company\Models\Company;
use PDO;
use PHPUnit\Framework\TestCase;

final class ModelFindManyTest extends TestCase
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
        foreach (['companies', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function makeCompany(string $suffix): array
    {
        $userId = User::insert([
            'email' => "findmany-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'F', 'last_name' => $suffix,
            'role_id' => self::$roleId,
            'is_active' => 1,
        ]);
        $companyId = Company::insert(['name' => "Co {$suffix}", 'owner_user_id' => $userId, 'status' => 'ACTIVE', 'hs_codes' => '[]']);

        return Company::find($companyId);
    }

    public function test_find_many_returns_rows_keyed_by_id(): void
    {
        $a = $this->makeCompany('a');
        $b = $this->makeCompany('b');

        $result = Company::findMany([$a['id'], $b['id']]);

        $this->assertCount(2, $result);
        $this->assertSame('Co a', $result[$a['id']]['name']);
        $this->assertSame('Co b', $result[$b['id']]['name']);
    }

    public function test_find_many_ignores_unknown_and_empty_ids(): void
    {
        $a = $this->makeCompany('c');

        $result = Company::findMany([$a['id'], 'does-not-exist', '', null]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($a['id'], $result);
    }

    public function test_find_many_with_empty_input_returns_empty_array_without_querying(): void
    {
        $this->assertSame([], Company::findMany([]));
    }

    public function test_find_many_excludes_soft_deleted_rows(): void
    {
        $a = $this->makeCompany('d');
        Company::softDelete($a['id']);

        $result = Company::findMany([$a['id']]);

        $this->assertSame([], $result);
    }
}
