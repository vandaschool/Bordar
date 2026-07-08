<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Company\Models\Company;
use App\Features\Company\Services\CompanyService;
use PDO;
use PHPUnit\Framework\TestCase;

final class CompanyServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $roleId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        $role = Role::findByName('Applicant');
        if ($role === null) {
            self::$roleId = Model::uuid();
            Role::insert(['id' => self::$roleId, 'name' => 'Applicant', 'description' => 'Applicant', 'permissions' => '[]']);
        } else {
            self::$roleId = $role['id'];
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE companies');
        self::$pdo->exec('TRUNCATE TABLE users');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function makeUser(string $email): string
    {
        return User::insert([
            'email' => $email,
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'role_id' => self::$roleId,
            'is_active' => 1,
            'email_verified_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function test_create_stores_hs_codes_and_defaults_status_pending(): void
    {
        $userId = $this->makeUser('owner1@example.com');
        $service = new CompanyService();

        $company = $service->create($userId, [
            'name' => 'Sun Exports',
            'registration_number' => '12345',
            'industry' => 'Food',
            'country' => 'Iran',
            'city' => 'Tehran',
            'website' => 'https://sunexports.example',
            'description' => 'desc',
            'hs_codes' => ['0806', '0813'],
        ]);

        $this->assertSame('PENDING', $company['status']);
        $this->assertSame(['0806', '0813'], CompanyService::hsCodes($company));
        $this->assertSame($userId, $company['owner_user_id']);
    }

    public function test_find_by_owner_returns_the_owners_company(): void
    {
        $userId = $this->makeUser('owner2@example.com');
        $service = new CompanyService();
        $service->create($userId, ['name' => 'Acme', 'registration_number' => '', 'website' => '', 'hs_codes' => []]);

        $found = Company::findByOwner($userId);

        $this->assertNotNull($found);
        $this->assertSame('Acme', $found['name']);
    }

    public function test_update_changes_fields_and_preserves_owner(): void
    {
        $userId = $this->makeUser('owner3@example.com');
        $service = new CompanyService();
        $company = $service->create($userId, ['name' => 'Old Name', 'registration_number' => '', 'website' => '', 'hs_codes' => ['0701']]);

        $updated = $service->update($company['id'], ['name' => 'New Name', 'registration_number' => '', 'website' => '', 'hs_codes' => ['0702']]);

        $this->assertSame('New Name', $updated['name']);
        $this->assertSame($userId, $updated['owner_user_id']);
        $this->assertSame(['0702'], CompanyService::hsCodes($updated));
    }
}
