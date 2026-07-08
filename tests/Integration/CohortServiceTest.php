<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Features\Cohort\Models\Cohort;
use App\Features\Cohort\Services\CohortService;
use PDO;
use PHPUnit\Framework\TestCase;

final class CohortServiceTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE cohorts');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public function test_create_converts_jalali_dates_to_utc(): void
    {
        $service = new CohortService();

        $cohort = $service->create([
            'name' => 'Cohort Spring 1405',
            'start_date' => '1405/01/01',
            'end_date' => '1405/04/01',
            'application_deadline' => '1404/12/15',
            'status' => 'UPCOMING',
            'description' => 'first cohort',
        ]);

        $this->assertSame('Cohort Spring 1405', $cohort['name']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cohort['start_date']);
        $this->assertNotNull($cohort['application_deadline']);
    }

    public function test_open_for_applications_excludes_past_deadline(): void
    {
        $service = new CohortService();

        // Deadline far in the past (Jalali ~ year 1300)
        $service->create([
            'name' => 'Expired Cohort',
            'start_date' => '1300/01/01',
            'end_date' => '1300/06/01',
            'application_deadline' => '1300/01/01',
            'status' => 'UPCOMING',
            'description' => '',
        ]);

        $service->create([
            'name' => 'Open Cohort',
            'start_date' => '1408/01/01',
            'end_date' => '1408/06/01',
            'application_deadline' => '1408/01/01',
            'status' => 'ACTIVE',
            'description' => '',
        ]);

        $open = Cohort::openForApplications();
        $names = array_column($open, 'name');

        $this->assertContains('Open Cohort', $names);
        $this->assertNotContains('Expired Cohort', $names);
    }

    public function test_open_for_applications_excludes_archived_status(): void
    {
        $service = new CohortService();
        $service->create([
            'name' => 'Archived Cohort',
            'start_date' => '1408/01/01',
            'end_date' => '1408/06/01',
            'application_deadline' => '1408/01/01',
            'status' => 'ARCHIVED',
            'description' => '',
        ]);

        $names = array_column(Cohort::openForApplications(), 'name');

        $this->assertNotContains('Archived Cohort', $names);
    }

    public function test_update_changes_status(): void
    {
        $service = new CohortService();
        $cohort = $service->create([
            'name' => 'To Activate',
            'start_date' => '1405/01/01',
            'end_date' => '1405/04/01',
            'application_deadline' => '',
            'status' => 'UPCOMING',
            'description' => '',
        ]);

        $updated = $service->update($cohort['id'], [
            'name' => 'To Activate',
            'start_date' => '1405/01/01',
            'end_date' => '1405/04/01',
            'application_deadline' => '',
            'status' => 'ACTIVE',
            'description' => '',
        ]);

        $this->assertSame('ACTIVE', $updated['status']);
        $this->assertNull($updated['application_deadline']);
    }
}
