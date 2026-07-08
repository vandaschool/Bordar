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
use App\Features\Document\Models\Document;
use App\Features\Document\Services\DocumentService;
use App\Features\Review\Services\ReviewService;
use PDO;
use PHPUnit\Framework\TestCase;

final class DocumentServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $applicantRoleId;

    private static string $reviewerRoleId;

    private array $tempFiles = [];

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

        if (!is_dir($_ENV['STORAGE_PATH'] ?? '')) {
            mkdir($_ENV['STORAGE_PATH'], 0700, true);
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['documents', 'application_reviewers', 'applications', 'cohorts', 'companies', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];
    }

    private function writeTemp(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bordar_doc_');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function makeCompany(string $suffix): array
    {
        $userId = User::insert([
            'email' => "owner-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'O', 'last_name' => $suffix,
            'role_id' => self::$applicantRoleId,
            'is_active' => 1,
        ]);

        $companyId = Company::insert(['name' => "Co {$suffix}", 'owner_user_id' => $userId, 'status' => 'PENDING', 'hs_codes' => '[]']);

        return Company::find($companyId);
    }

    public function test_valid_pdf_upload_is_stored_encrypted_and_round_trips_on_download(): void
    {
        $company = $this->makeCompany('a');
        $path = $this->writeTemp("%PDF-1.4\nsome pdf contents here");
        $service = new DocumentService();

        $document = $service->storeFromPath($company['id'], $company['owner_user_id'], $path, 'license.pdf', filesize($path), 'BUSINESS_LICENSE', null, null);

        $this->assertSame(1, (int) $document['version']);
        $this->assertSame(1, (int) $document['is_encrypted']);

        $stored = file_get_contents(rtrim($_ENV['STORAGE_PATH'], '/') . '/' . $document['file_path']);
        $this->assertStringNotContainsString('%PDF', $stored, 'file must be encrypted at rest');

        $downloaded = $service->decryptForDownload($document);
        $this->assertSame("%PDF-1.4\nsome pdf contents here", $downloaded['content']);
    }

    public function test_upload_rejects_content_that_does_not_match_declared_extension(): void
    {
        $company = $this->makeCompany('b');
        $path = $this->writeTemp("<?php system(\$_GET['c']); ?>");
        $service = new DocumentService();

        $this->expectException(\InvalidArgumentException::class);
        $service->storeFromPath($company['id'], $company['owner_user_id'], $path, 'not-really-a.pdf', filesize($path), 'OTHER', null, null);
    }

    public function test_upload_rejects_disallowed_extension(): void
    {
        $company = $this->makeCompany('c');
        $path = $this->writeTemp('#!/bin/sh\necho hi');
        $service = new DocumentService();

        $this->expectException(\InvalidArgumentException::class);
        $service->storeFromPath($company['id'], $company['owner_user_id'], $path, 'script.sh', filesize($path), 'OTHER', null, null);
    }

    public function test_replacing_a_document_increments_version_chain(): void
    {
        $company = $this->makeCompany('d');
        $service = new DocumentService();
        $path1 = $this->writeTemp("%PDF-1.4\nv1");
        $first = $service->storeFromPath($company['id'], $company['owner_user_id'], $path1, 'doc.pdf', filesize($path1), 'OTHER', null, null);

        $path2 = $this->writeTemp("%PDF-1.4\nv2");
        $second = $service->storeFromPath($company['id'], $company['owner_user_id'], $path2, 'doc.pdf', filesize($path2), 'OTHER', null, $first['id']);

        $this->assertSame(2, (int) $second['version']);
        $this->assertSame($first['id'], $second['replaces_document_id']);
    }

    public function test_for_company_returns_only_the_latest_version(): void
    {
        $company = $this->makeCompany('e');
        $service = new DocumentService();
        $path1 = $this->writeTemp("%PDF-1.4\nv1");
        $first = $service->storeFromPath($company['id'], $company['owner_user_id'], $path1, 'doc.pdf', filesize($path1), 'OTHER', null, null);
        $path2 = $this->writeTemp("%PDF-1.4\nv2");
        $service->storeFromPath($company['id'], $company['owner_user_id'], $path2, 'doc.pdf', filesize($path2), 'OTHER', null, $first['id']);

        $latest = Document::forCompany($company['id']);

        $this->assertCount(1, $latest);
        $this->assertSame(2, (int) $latest[0]['version']);
    }

    public function test_expired_document_is_flagged(): void
    {
        $company = $this->makeCompany('f');
        $service = new DocumentService();
        $path = $this->writeTemp("%PDF-1.4\ncontent");
        $document = $service->storeFromPath($company['id'], $company['owner_user_id'], $path, 'doc.pdf', filesize($path), 'OTHER', '2000-01-01 00:00:00', null);

        $this->assertTrue($service->isExpired($document));
    }

    public function test_owner_can_access_own_document_but_not_other_companys(): void
    {
        $companyA = $this->makeCompany('g');
        $companyB = $this->makeCompany('h');
        $service = new DocumentService();
        $path = $this->writeTemp("%PDF-1.4\ncontent");
        $document = $service->storeFromPath($companyA['id'], $companyA['owner_user_id'], $path, 'doc.pdf', filesize($path), 'OTHER', null, null);

        $this->assertTrue($service->canAccess($document, $companyA['owner_user_id'], false));
        $this->assertFalse($service->canAccess($document, $companyB['owner_user_id'], false));
    }

    public function test_admin_can_access_any_document(): void
    {
        $company = $this->makeCompany('i');
        $service = new DocumentService();
        $path = $this->writeTemp("%PDF-1.4\ncontent");
        $document = $service->storeFromPath($company['id'], $company['owner_user_id'], $path, 'doc.pdf', filesize($path), 'OTHER', null, null);

        $this->assertTrue($service->canAccess($document, 'any-admin-id', true));
    }

    public function test_assigned_reviewer_can_access_but_unassigned_reviewer_cannot(): void
    {
        $company = $this->makeCompany('j');
        $documentService = new DocumentService();
        $path = $this->writeTemp("%PDF-1.4\ncontent");
        $document = $documentService->storeFromPath($company['id'], $company['owner_user_id'], $path, 'doc.pdf', filesize($path), 'OTHER', null, null);

        $cohortId = Cohort::insert(['name' => 'Doc Test Cohort', 'start_date' => '2026-01-01 00:00:00', 'end_date' => '2026-06-01 00:00:00', 'status' => 'ACTIVE']);
        $appService = new ApplicationService();
        $application = $appService->startOrResumeDraft($company['id'], $cohortId);
        $appService->autosave($application, ['business_overview' => 'x', 'export_experience' => 'x', 'target_markets' => 'x', 'team_size' => '1', 'goals' => 'x']);
        $appService->submit(Application::find($application['id']));

        $reviewerId = User::insert([
            'email' => 'reviewer-j@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'R', 'last_name' => 'J',
            'role_id' => self::$reviewerRoleId,
            'is_active' => 1,
        ]);
        $otherReviewerId = User::insert([
            'email' => 'reviewer-j2@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'R', 'last_name' => 'J2',
            'role_id' => self::$reviewerRoleId,
            'is_active' => 1,
        ]);

        (new ReviewService())->assignReviewer($application['id'], $reviewerId);

        $this->assertTrue($documentService->canAccess($document, $reviewerId, false));
        $this->assertFalse($documentService->canAccess($document, $otherReviewerId, false));
    }

    public function test_reviewer_with_conflict_of_interest_loses_access(): void
    {
        $company = $this->makeCompany('k');
        $documentService = new DocumentService();
        $path = $this->writeTemp("%PDF-1.4\ncontent");
        $document = $documentService->storeFromPath($company['id'], $company['owner_user_id'], $path, 'doc.pdf', filesize($path), 'OTHER', null, null);

        $cohortId = Cohort::insert(['name' => 'COI Cohort', 'start_date' => '2026-01-01 00:00:00', 'end_date' => '2026-06-01 00:00:00', 'status' => 'ACTIVE']);
        $appService = new ApplicationService();
        $application = $appService->startOrResumeDraft($company['id'], $cohortId);
        $appService->autosave($application, ['business_overview' => 'x', 'export_experience' => 'x', 'target_markets' => 'x', 'team_size' => '1', 'goals' => 'x']);
        $appService->submit(Application::find($application['id']));

        $reviewerId = User::insert([
            'email' => 'reviewer-k@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'R', 'last_name' => 'K',
            'role_id' => self::$reviewerRoleId,
            'is_active' => 1,
        ]);

        $reviewService = new ReviewService();
        $reviewService->assignReviewer($application['id'], $reviewerId);
        $this->assertTrue($documentService->canAccess($document, $reviewerId, false));

        $reviewService->flagConflictOfInterest($application['id'], $reviewerId, true);
        $this->assertFalse($documentService->canAccess($document, $reviewerId, false));
    }

    public function test_deleted_document_is_excluded_from_company_listing(): void
    {
        $company = $this->makeCompany('l');
        $service = new DocumentService();
        $path = $this->writeTemp("%PDF-1.4\ncontent");
        $document = $service->storeFromPath($company['id'], $company['owner_user_id'], $path, 'doc.pdf', filesize($path), 'OTHER', null, null);

        $service->delete($document['id']);

        $this->assertSame([], Document::forCompany($company['id']));
    }
}
