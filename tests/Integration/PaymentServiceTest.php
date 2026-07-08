<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Cohort\Models\Cohort;
use App\Features\Cohort\Models\CompanyCohort;
use App\Features\Company\Models\Company;
use App\Features\Payment\Models\Payment;
use App\Features\Payment\Services\PaymentService;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeZarinpalClient;

final class PaymentServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $applicantRoleId;

    private array $tempFiles = [];

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        $role = Role::findByName('Applicant');
        self::$applicantRoleId = $role['id'] ?? Model::uuid();
        if ($role === null) {
            Role::insert(['id' => self::$applicantRoleId, 'name' => 'Applicant', 'description' => 'Applicant', 'permissions' => '[]']);
        }

        if (!is_dir($_ENV['STORAGE_PATH'] ?? '')) {
            mkdir($_ENV['STORAGE_PATH'], 0700, true);
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['payments', 'invoices', 'images', 'company_cohorts', 'cohorts', 'companies', 'users'] as $table) {
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
        $path = tempnam(sys_get_temp_dir(), 'bordar_receipt_');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    /** @return array{company: array, cohort_id: string} */
    private function makeAcceptedCompany(string $suffix): array
    {
        $userId = User::insert([
            'email' => "founder-pay-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'F', 'last_name' => $suffix,
            'role_id' => self::$applicantRoleId,
            'is_active' => 1,
        ]);

        $companyId = Company::insert(['name' => "Co {$suffix}", 'owner_user_id' => $userId, 'status' => 'ACTIVE', 'hs_codes' => '[]']);
        $cohortId = Cohort::insert(['name' => "Cohort {$suffix}", 'start_date' => '2026-01-01 00:00:00', 'end_date' => '2026-06-01 00:00:00', 'status' => 'ACTIVE']);
        CompanyCohort::upsertStatus($companyId, $cohortId, 'ACCEPTED');

        return ['company' => Company::find($companyId), 'cohort_id' => $cohortId];
    }

    public function test_get_or_create_invoice_is_idempotent(): void
    {
        $ctx = $this->makeAcceptedCompany('a');
        $service = new PaymentService();

        $first = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 2);
        $second = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 3);

        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(2, (int) $second['installment_count'], 'second call must not change the already-issued plan');
    }

    public function test_installment_plan_splits_amount_with_remainder_on_last_installment(): void
    {
        $ctx = $this->makeAcceptedCompany('b');
        $service = new PaymentService();
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 3);

        $plan = $service->installmentPlan($invoice);

        $this->assertCount(3, $plan);
        $sum = array_sum(array_column($plan, 'amount'));
        $this->assertEqualsWithDelta((float) $invoice['amount'], $sum, 0.01);
    }

    public function test_zarinpal_payment_start_creates_pending_payment_and_reuses_authority_on_retry(): void
    {
        $ctx = $this->makeAcceptedCompany('c');
        $fake = new FakeZarinpalClient('merchant', true);
        $fake->responses[] = ['data' => ['code' => 100, 'authority' => 'AUTH-1'], 'errors' => []];
        $service = new PaymentService($fake);
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);

        $first = $service->startZarinpalPayment($invoice, 1, 'https://bordar.local/payment/callback', null, null);
        $second = $service->startZarinpalPayment($invoice, 1, 'https://bordar.local/payment/callback', null, null);

        $this->assertSame($first['payment_id'], $second['payment_id']);
        $this->assertCount(1, $fake->calls, 'retrying an unfinished payment must not open a second gateway transaction');
    }

    public function test_zarinpal_verify_marks_payment_paid(): void
    {
        $ctx = $this->makeAcceptedCompany('d');
        $fake = new FakeZarinpalClient('merchant', true);
        $fake->responses[] = ['data' => ['code' => 100, 'authority' => 'AUTH-D'], 'errors' => []];
        $fake->responses[] = ['data' => ['code' => 100, 'ref_id' => 555], 'errors' => []];
        $service = new PaymentService($fake);
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);

        $started = $service->startZarinpalPayment($invoice, 1, 'https://bordar.local/payment/callback', null, null);
        $result = $service->verifyZarinpalCallback($started['payment_id'], 'AUTH-D', 'OK');

        $this->assertSame('PAID', $result['status']);
        $this->assertSame('555', $result['transaction_id']);
    }

    public function test_zarinpal_cancelled_status_marks_payment_failed_without_calling_verify(): void
    {
        $ctx = $this->makeAcceptedCompany('e');
        $fake = new FakeZarinpalClient('merchant', true);
        $fake->responses[] = ['data' => ['code' => 100, 'authority' => 'AUTH-E'], 'errors' => []];
        $service = new PaymentService($fake);
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);

        $started = $service->startZarinpalPayment($invoice, 1, 'https://bordar.local/payment/callback', null, null);
        $result = $service->verifyZarinpalCallback($started['payment_id'], 'AUTH-E', 'NOK');

        $this->assertSame('FAILED', $result['status']);
        $this->assertCount(1, $fake->calls, 'a cancelled gateway redirect must not trigger a verify API call');
    }

    public function test_manual_bank_transfer_submission_stores_encrypted_receipt(): void
    {
        $ctx = $this->makeAcceptedCompany('f');
        $service = new PaymentService();
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);
        $receiptPath = $this->writeTemp("\xFF\xD8\xFFfake jpeg receipt bytes");

        $payment = $service->submitManualBankTransfer(
            $invoice, 1, $ctx['company']['owner_user_id'], 'TRK-123', '1405/04/01',
            ['name' => 'receipt.jpg', 'tmp_name' => $receiptPath, 'size' => filesize($receiptPath), 'error' => UPLOAD_ERR_OK]
        );

        $this->assertSame('PENDING', $payment['status']);
        $this->assertSame('TRK-123', $payment['bank_tracking_number']);
        $this->assertNotNull($payment['manual_receipt_image_id']);
    }

    public function test_manual_bank_transfer_rejects_disguised_file(): void
    {
        $ctx = $this->makeAcceptedCompany('g');
        $service = new PaymentService();
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);
        $maliciousPath = $this->writeTemp("<?php system(\$_GET['c']); ?>");

        $this->expectException(\InvalidArgumentException::class);
        $service->submitManualBankTransfer(
            $invoice, 1, $ctx['company']['owner_user_id'], 'TRK-999', '1405/04/01',
            ['name' => 'receipt.jpg', 'tmp_name' => $maliciousPath, 'size' => filesize($maliciousPath), 'error' => UPLOAD_ERR_OK]
        );
    }

    public function test_approving_manual_payment_marks_it_paid_and_completes_the_invoice(): void
    {
        $ctx = $this->makeAcceptedCompany('h');
        $service = new PaymentService();
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);
        $receiptPath = $this->writeTemp("\xFF\xD8\xFFfake jpeg receipt bytes");
        $payment = $service->submitManualBankTransfer(
            $invoice, 1, $ctx['company']['owner_user_id'], 'TRK-1', '1405/04/01',
            ['name' => 'r.jpg', 'tmp_name' => $receiptPath, 'size' => filesize($receiptPath), 'error' => UPLOAD_ERR_OK]
        );

        $adminId = $ctx['company']['owner_user_id']; // any valid user id works as the reviewer for this assertion
        $approved = $service->approveManualPayment($payment['id'], $adminId);

        $this->assertSame('PAID', $approved['status']);
        $this->assertTrue($service->isFullyPaid(\App\Features\Payment\Models\Invoice::find($invoice['id'])));
    }

    public function test_rejecting_manual_payment_records_reason_and_keeps_installment_unpaid(): void
    {
        $ctx = $this->makeAcceptedCompany('i');
        $service = new PaymentService();
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);
        $receiptPath = $this->writeTemp("\xFF\xD8\xFFfake jpeg receipt bytes");
        $payment = $service->submitManualBankTransfer(
            $invoice, 1, $ctx['company']['owner_user_id'], 'TRK-2', '1405/04/01',
            ['name' => 'r.jpg', 'tmp_name' => $receiptPath, 'size' => filesize($receiptPath), 'error' => UPLOAD_ERR_OK]
        );

        $rejected = $service->rejectManualPayment($payment['id'], $ctx['company']['owner_user_id'], 'مغایرت مبلغ');

        $this->assertSame('FAILED', $rejected['status']);
        $this->assertSame('مغایرت مبلغ', $rejected['rejected_reason']);
        $this->assertFalse($service->isFullyPaid(\App\Features\Payment\Models\Invoice::find($invoice['id'])));
    }

    public function test_cannot_start_payment_for_an_already_paid_installment(): void
    {
        $ctx = $this->makeAcceptedCompany('j');
        $service = new PaymentService();
        $invoice = $service->getOrCreateInvoice($ctx['company']['id'], $ctx['cohort_id'], 1);
        $receiptPath = $this->writeTemp("\xFF\xD8\xFFfake jpeg receipt bytes");
        $payment = $service->submitManualBankTransfer(
            $invoice, 1, $ctx['company']['owner_user_id'], 'TRK-3', '1405/04/01',
            ['name' => 'r.jpg', 'tmp_name' => $receiptPath, 'size' => filesize($receiptPath), 'error' => UPLOAD_ERR_OK]
        );
        $service->approveManualPayment($payment['id'], $ctx['company']['owner_user_id']);

        $this->expectException(\RuntimeException::class);
        $service->submitManualBankTransfer(
            $invoice, 1, $ctx['company']['owner_user_id'], 'TRK-4', '1405/04/02',
            ['name' => 'r2.jpg', 'tmp_name' => $this->writeTemp("\xFF\xD8\xFFmore"), 'size' => 10, 'error' => UPLOAD_ERR_OK]
        );
    }
}
