<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Company\Models\Company;
use App\Features\Company\Services\CompanyService;
use App\Features\Onboarding\Services\OnboardingService;
use PDO;
use PHPUnit\Framework\TestCase;

final class OnboardingServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $applicantRoleId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        $role = Role::findByName('Applicant');
        self::$applicantRoleId = $role['id'] ?? Model::uuid();
        if ($role === null) {
            Role::insert(['id' => self::$applicantRoleId, 'name' => 'Applicant', 'description' => 'Applicant', 'permissions' => '[]']);
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['company_onboarding_progress', 'documents', 'companies', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function makeCompany(string $suffix, array $overrides = []): array
    {
        $userId = User::insert([
            'email' => "founder-ob-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'F', 'last_name' => $suffix,
            'role_id' => self::$applicantRoleId,
            'is_active' => 1,
        ]);

        $companyId = Company::insert(array_merge([
            'name' => "Co {$suffix}",
            'owner_user_id' => $userId,
            'status' => 'ACTIVE',
            'hs_codes' => '[]',
        ], $overrides));

        return Company::find($companyId);
    }

    public function test_incomplete_profile_is_not_marked_complete(): void
    {
        $company = $this->makeCompany('a');
        $service = new OnboardingService();

        $checklist = $service->checklist($company);
        $profileItem = current(array_filter($checklist, static fn ($i) => $i['key'] === 'complete_company_profile'));

        $this->assertFalse($profileItem['completed']);
    }

    public function test_fully_filled_profile_is_marked_complete(): void
    {
        $company = $this->makeCompany('b', [
            'industry' => 'Food', 'country' => 'Iran', 'city' => 'Tehran', 'description' => 'We export dates.',
        ]);
        $service = new OnboardingService();

        $checklist = $service->checklist($company);
        $profileItem = current(array_filter($checklist, static fn ($i) => $i['key'] === 'complete_company_profile'));

        $this->assertTrue($profileItem['completed']);
    }

    public function test_manual_item_can_be_toggled_on_and_off(): void
    {
        $company = $this->makeCompany('c');
        $service = new OnboardingService();

        $service->toggleManualItem($company['id'], 'join_orientation_webinar', true);
        $checklist = $service->checklist($company);
        $item = current(array_filter($checklist, static fn ($i) => $i['key'] === 'join_orientation_webinar'));
        $this->assertTrue($item['completed']);

        $service->toggleManualItem($company['id'], 'join_orientation_webinar', false);
        $checklist = $service->checklist($company);
        $item = current(array_filter($checklist, static fn ($i) => $i['key'] === 'join_orientation_webinar'));
        $this->assertFalse($item['completed']);
    }

    public function test_toggling_an_auto_derived_item_is_rejected(): void
    {
        $company = $this->makeCompany('d');
        $service = new OnboardingService();

        $this->expectException(\InvalidArgumentException::class);
        $service->toggleManualItem($company['id'], 'complete_company_profile', true);
    }

    public function test_progress_percent_reflects_completed_items(): void
    {
        $company = $this->makeCompany('e', [
            'industry' => 'Food', 'country' => 'Iran', 'city' => 'Tehran', 'description' => 'desc',
        ]);
        $service = new OnboardingService();

        $before = $service->progressPercent($company);

        $service->toggleManualItem($company['id'], 'join_orientation_webinar', true);
        $after = $service->progressPercent($company);

        $this->assertGreaterThan($before, $after);
    }

    public function test_welcome_sequence_is_sent_only_once(): void
    {
        $company = $this->makeCompany('f');
        $service = new OnboardingService();

        $service->sendWelcomeSequenceIfNeeded($company);
        $countAfterFirst = (int) self::$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = '{$company['owner_user_id']}'")->fetchColumn();

        $service->sendWelcomeSequenceIfNeeded($company);
        $countAfterSecond = (int) self::$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = '{$company['owner_user_id']}'")->fetchColumn();

        $this->assertGreaterThan(0, $countAfterFirst);
        $this->assertSame($countAfterFirst, $countAfterSecond, 'welcome sequence must not fire twice for the same company');
    }

    public function test_complete_tour_sets_timestamp_on_company(): void
    {
        $company = $this->makeCompany('g');
        $service = new OnboardingService();

        $service->completeTour($company['id']);

        $this->assertNotNull(Company::find($company['id'])['onboarding_tour_completed_at']);
    }
}
