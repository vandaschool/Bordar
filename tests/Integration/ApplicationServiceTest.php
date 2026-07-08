<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Application\Models\Application;
use App\Features\Application\Services\ApplicationService;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Cohort\Models\Cohort;
use App\Features\Company\Models\Company;
use PDO;
use PHPUnit\Framework\TestCase;

final class ApplicationServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $roleId;

    private static string $cohortId;

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
        foreach (['applications', 'companies', 'cohorts', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        self::$cohortId = Cohort::insert([
            'name' => 'Test Cohort',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-06-01 00:00:00',
            'application_deadline' => '2027-01-01 00:00:00',
            'status' => 'ACTIVE',
        ]);
    }

    private function makeCompany(string $emailSuffix): array
    {
        $userId = User::insert([
            'email' => "founder-{$emailSuffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'F',
            'last_name' => 'L',
            'role_id' => self::$roleId,
            'is_active' => 1,
            'email_verified_at' => gmdate('Y-m-d H:i:s'),
        ]);

        $companyId = Company::insert([
            'name' => "Company {$emailSuffix}",
            'owner_user_id' => $userId,
            'status' => 'PENDING',
            'hs_codes' => '[]',
        ]);

        return Company::find($companyId);
    }

    public function test_start_or_resume_draft_creates_a_new_draft(): void
    {
        $company = $this->makeCompany('a');
        $service = new ApplicationService();

        $application = $service->startOrResumeDraft($company['id'], self::$cohortId);

        $this->assertSame('DRAFT', $application['status']);
        $this->assertSame(1, (int) $application['version']);
    }

    public function test_start_or_resume_draft_returns_existing_draft_instead_of_duplicating(): void
    {
        $company = $this->makeCompany('b');
        $service = new ApplicationService();

        $first = $service->startOrResumeDraft($company['id'], self::$cohortId);
        $second = $service->startOrResumeDraft($company['id'], self::$cohortId);

        $this->assertSame($first['id'], $second['id']);
        $this->assertCount(1, Application::forCompany($company['id']));
    }

    public function test_autosave_merges_fields_and_increments_version(): void
    {
        $company = $this->makeCompany('c');
        $service = new ApplicationService();
        $application = $service->startOrResumeDraft($company['id'], self::$cohortId);

        $result = $service->autosave($application, ['business_overview' => 'We export dates.']);
        $this->assertSame(2, $result['version']);

        $reloaded = Application::find($application['id']);
        $data = json_decode($reloaded['data'], true);
        $this->assertSame('We export dates.', $data['business_overview']);

        $result2 = $service->autosave($reloaded, ['export_experience' => '5 years']);
        $this->assertSame(3, $result2['version']);

        $reloaded2 = Application::find($application['id']);
        $data2 = json_decode($reloaded2['data'], true);
        $this->assertSame('We export dates.', $data2['business_overview'], 'previous field must survive the next autosave merge');
        $this->assertSame('5 years', $data2['export_experience']);
    }

    public function test_autosave_rejects_non_draft_applications(): void
    {
        $company = $this->makeCompany('d');
        $service = new ApplicationService();
        $application = $service->startOrResumeDraft($company['id'], self::$cohortId);
        Application::update($application['id'], ['status' => 'SUBMITTED']);

        $this->expectException(\RuntimeException::class);
        $service->autosave(Application::find($application['id']), ['goals' => 'x']);
    }

    public function test_submit_fails_when_required_fields_missing(): void
    {
        $company = $this->makeCompany('e');
        $service = new ApplicationService();
        $application = $service->startOrResumeDraft($company['id'], self::$cohortId);

        $this->assertNotEmpty($service->missingFields($application));

        $this->expectException(\InvalidArgumentException::class);
        $service->submit($application);
    }

    public function test_submit_succeeds_once_all_required_fields_present(): void
    {
        $company = $this->makeCompany('f');
        $service = new ApplicationService();
        $application = $service->startOrResumeDraft($company['id'], self::$cohortId);

        $service->autosave($application, [
            'business_overview' => 'x',
            'export_experience' => 'x',
            'target_markets' => 'x',
            'team_size' => '5',
            'goals' => 'x',
        ]);

        $application = Application::find($application['id']);
        $this->assertSame([], $service->missingFields($application));

        $submitted = $service->submit($application);

        $this->assertSame('SUBMITTED', $submitted['status']);
        $this->assertNotNull($submitted['submitted_at']);
    }

    public function test_application_is_isolated_between_companies(): void
    {
        $companyA = $this->makeCompany('g');
        $companyB = $this->makeCompany('h');
        $service = new ApplicationService();

        $application = $service->startOrResumeDraft($companyA['id'], self::$cohortId);

        $this->assertNotNull(Application::findScoped($application['id'], $companyA['id']));
        $this->assertNull(Application::findScoped($application['id'], $companyB['id']));
    }
}
