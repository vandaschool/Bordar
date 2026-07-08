<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Application\Models\Application;
use App\Features\Application\Services\ApplicationService;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Cohort\Models\CompanyCohort;
use App\Features\Cohort\Models\Cohort;
use App\Features\Company\Models\Company;
use App\Features\Review\Models\ApplicationClarification;
use App\Features\Review\Models\ApplicationReviewer;
use App\Features\Review\Services\ReviewService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ReviewServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $applicantRoleId;

    private static string $reviewerRoleId;

    private static string $cohortId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        foreach (['Applicant', 'Reviewer'] as $roleName) {
            $role = Role::findByName($roleName);
            $id = $role['id'] ?? Model::uuid();
            if ($role === null) {
                Role::insert(['id' => $id, 'name' => $roleName, 'description' => $roleName, 'permissions' => '[]']);
            }
            if ($roleName === 'Applicant') {
                self::$applicantRoleId = $id;
            } else {
                self::$reviewerRoleId = $id;
            }
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['application_clarifications', 'application_reviewers', 'applications', 'company_cohorts', 'companies', 'cohorts', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        self::$cohortId = Cohort::insert([
            'name' => 'Review Test Cohort',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-06-01 00:00:00',
            'status' => 'ACTIVE',
        ]);
    }

    private function makeReviewer(string $suffix): string
    {
        return User::insert([
            'email' => "reviewer-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'R', 'last_name' => $suffix,
            'role_id' => self::$reviewerRoleId,
            'is_active' => 1,
        ]);
    }

    private function makeSubmittedApplication(string $suffix): array
    {
        $userId = User::insert([
            'email' => "founder-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'F', 'last_name' => $suffix,
            'role_id' => self::$applicantRoleId,
            'is_active' => 1,
        ]);

        $companyId = Company::insert([
            'name' => "Company {$suffix}",
            'owner_user_id' => $userId,
            'status' => 'PENDING',
            'hs_codes' => '[]',
        ]);

        $appService = new ApplicationService();
        $application = $appService->startOrResumeDraft($companyId, self::$cohortId);
        $appService->autosave($application, [
            'business_overview' => 'x', 'export_experience' => 'x',
            'target_markets' => 'x', 'team_size' => '3', 'goals' => 'x',
        ]);
        $application = Application::find($application['id']);
        $appService->submit($application);

        return Application::find($application['id']);
    }

    public function test_assigning_a_reviewer_moves_application_to_under_review(): void
    {
        $application = $this->makeSubmittedApplication('a');
        $reviewerId = $this->makeReviewer('a');
        $service = new ReviewService();

        $service->assignReviewer($application['id'], $reviewerId);

        $this->assertSame('UNDER_REVIEW', Application::find($application['id'])['status']);
    }

    public function test_assigning_same_reviewer_twice_throws(): void
    {
        $application = $this->makeSubmittedApplication('b');
        $reviewerId = $this->makeReviewer('b');
        $service = new ReviewService();
        $service->assignReviewer($application['id'], $reviewerId);

        $this->expectException(\RuntimeException::class);
        $service->assignReviewer($application['id'], $reviewerId);
    }

    public function test_workload_count_increases_with_active_assignments(): void
    {
        $reviewerId = $this->makeReviewer('c');
        $service = new ReviewService();

        $this->assertSame(0, ApplicationReviewer::activeWorkloadCount($reviewerId));

        $appOne = $this->makeSubmittedApplication('c1');
        $service->assignReviewer($appOne['id'], $reviewerId);

        $this->assertSame(1, ApplicationReviewer::activeWorkloadCount($reviewerId));
    }

    public function test_conflict_of_interest_excludes_from_workload(): void
    {
        $application = $this->makeSubmittedApplication('d');
        $reviewerId = $this->makeReviewer('d');
        $service = new ReviewService();
        $service->assignReviewer($application['id'], $reviewerId);

        $service->flagConflictOfInterest($application['id'], $reviewerId, true);

        $this->assertSame(0, ApplicationReviewer::activeWorkloadCount($reviewerId));
    }

    public function test_submit_review_locks_further_edits(): void
    {
        $application = $this->makeSubmittedApplication('e');
        $reviewerId = $this->makeReviewer('e');
        $service = new ReviewService();
        $service->assignReviewer($application['id'], $reviewerId);

        $service->submitReview($application['id'], $reviewerId, 85.0, 'good application');

        $this->expectException(\RuntimeException::class);
        $service->submitReview($application['id'], $reviewerId, 90.0, 'trying to change');
    }

    public function test_score_out_of_range_is_rejected(): void
    {
        $application = $this->makeSubmittedApplication('f');
        $reviewerId = $this->makeReviewer('f');
        $service = new ReviewService();
        $service->assignReviewer($application['id'], $reviewerId);

        $this->expectException(\InvalidArgumentException::class);
        $service->submitReview($application['id'], $reviewerId, 150.0, 'invalid');
    }

    public function test_score_summary_detects_large_variance_between_reviewers(): void
    {
        $application = $this->makeSubmittedApplication('g');
        $reviewerA = $this->makeReviewer('g1');
        $reviewerB = $this->makeReviewer('g2');
        $service = new ReviewService();

        $service->assignReviewer($application['id'], $reviewerA);
        $service->assignReviewer($application['id'], $reviewerB);
        $service->submitReview($application['id'], $reviewerA, 90.0, 'great');
        $service->submitReview($application['id'], $reviewerB, 40.0, 'weak');

        $summary = $service->scoreSummary($application['id']);

        $this->assertTrue($summary['hasVariance']);
        $this->assertSame(65.0, $summary['average']);
    }

    public function test_conflicted_reviewer_score_excluded_from_average(): void
    {
        $application = $this->makeSubmittedApplication('h');
        $reviewerA = $this->makeReviewer('h1');
        $service = new ReviewService();
        $service->assignReviewer($application['id'], $reviewerA);
        $service->flagConflictOfInterest($application['id'], $reviewerA, true);
        $service->submitReview($application['id'], $reviewerA, 10.0, 'should be excluded');

        $summary = $service->scoreSummary($application['id']);

        $this->assertNull($summary['average']);
    }

    public function test_clarification_request_sets_pending_info_and_response_reverts_to_under_review(): void
    {
        $application = $this->makeSubmittedApplication('i');
        $reviewerId = $this->makeReviewer('i');
        $service = new ReviewService();
        $service->assignReviewer($application['id'], $reviewerId);

        $service->requestClarification($application['id'], $reviewerId, 'Please clarify export volume.');
        $this->assertSame('PENDING_INFO', Application::find($application['id'])['status']);

        $clarifications = ApplicationClarification::forApplication($application['id']);
        $this->assertCount(1, $clarifications);
        $this->assertTrue(ApplicationClarification::hasPending($application['id']));

        $service->respondClarification($clarifications[0]['id'], 'We export 500 tons per year.');

        $this->assertFalse(ApplicationClarification::hasPending($application['id']));
        $this->assertSame('UNDER_REVIEW', Application::find($application['id'])['status']);
    }

    public function test_responding_to_an_already_answered_clarification_throws(): void
    {
        $application = $this->makeSubmittedApplication('j');
        $reviewerId = $this->makeReviewer('j');
        $service = new ReviewService();
        $service->assignReviewer($application['id'], $reviewerId);
        $service->requestClarification($application['id'], $reviewerId, 'Q1');
        $clarification = ApplicationClarification::forApplication($application['id'])[0];
        $service->respondClarification($clarification['id'], 'A1');

        $this->expectException(\RuntimeException::class);
        $service->respondClarification($clarification['id'], 'A1 again');
    }

    public function test_manual_override_updates_status_and_syncs_company_cohort(): void
    {
        $application = $this->makeSubmittedApplication('k');
        $adminId = User::insert([
            'email' => 'admin-review@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'Ad', 'last_name' => 'Min',
            'role_id' => self::$applicantRoleId,
            'is_active' => 1,
        ]);
        $service = new ReviewService();

        $updated = $service->manualOverride($application['id'], 'ACCEPTED', 'Strong fit, approved by admin override', $adminId);

        $this->assertSame('ACCEPTED', $updated['status']);

        $pivot = CompanyCohort::find($application['company_id'], $application['cohort_id']);
        $this->assertSame('ACCEPTED', $pivot['status']);
        $this->assertNotNull($pivot['joined_at']);
    }

    public function test_manual_override_rejects_invalid_status(): void
    {
        $application = $this->makeSubmittedApplication('l');
        $service = new ReviewService();

        $this->expectException(\InvalidArgumentException::class);
        $service->manualOverride($application['id'], 'NOT_A_REAL_STATUS', 'bad', 'admin-id');
    }
}
