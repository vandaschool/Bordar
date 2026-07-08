<?php

declare(strict_types=1);

namespace App\Features\Payment\Services;

use App\Config\Constants;
use App\Core\AuditLog;
use App\Core\Model;
use App\Core\Notifier;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Company\Models\Company;
use App\Features\Payment\Models\Image;
use App\Features\Payment\Models\Invoice;
use App\Features\Payment\Models\Payment;
use App\Lib\FileEncryptor;
use App\Lib\FileUploader;
use App\Lib\ZarinpalClient;

final class PaymentService
{
    private const RECEIPT_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    public function __construct(private readonly ZarinpalClient $zarinpal = new ZarinpalClient())
    {
    }

    /** Idempotent: returns the existing invoice if one was already issued for this company+cohort. */
    public function getOrCreateInvoice(string $companyId, string $cohortId, int $installmentCount = 1, ?string $createdByUserId = null): array
    {
        $existing = Invoice::forCompanyAndCohort($companyId, $cohortId);

        if ($existing !== null) {
            return $existing;
        }

        $installmentCount = max(1, min(Constants::MAX_INSTALLMENTS, $installmentCount));

        $id = Invoice::insert([
            'company_id' => $companyId,
            'cohort_id' => $cohortId,
            'amount' => Constants::PROGRAM_FEE_IRR,
            'currency' => 'IRR',
            'installment_count' => $installmentCount,
            'due_date' => gmdate('Y-m-d H:i:s', time() + 14 * 86400),
            'created_by_id' => $createdByUserId,
        ]);

        $invoice = Invoice::find($id);

        AuditLog::record('payment.invoice_created', 'Invoice', $id, null, ['amount' => $invoice['amount'], 'installments' => $installmentCount]);

        $owner = Company::find($companyId)['owner_user_id'] ?? null;
        if ($owner !== null) {
            Notifier::send(
                'payment.due',
                $owner,
                'صورت‌حساب شهریه برنامه صادر شد',
                'صورت‌حساب شهریه شرکت‌کنندگی شما در برنامه صادر شد. لطفاً نسبت به پرداخت اقدام کنید.',
                '/payment'
            );
        }

        return $invoice;
    }

    /** @return array<int, array{number: int, amount: float, status: string}> */
    public function installmentPlan(array $invoice): array
    {
        $count = (int) $invoice['installment_count'];
        $total = (float) $invoice['amount'];
        $base = floor($total / $count);
        $remainder = $total - ($base * $count);

        $payments = Payment::forInvoice($invoice['id']);
        $plan = [];

        for ($n = 1; $n <= $count; $n++) {
            $amount = $base + ($n === $count ? $remainder : 0);
            $status = Payment::isInstallmentPaid($invoice['id'], $n) ? 'PAID' : 'PENDING';
            $plan[] = ['number' => $n, 'amount' => $amount, 'status' => $status];
        }

        return $plan;
    }

    public function isFullyPaid(array $invoice): bool
    {
        foreach ($this->installmentPlan($invoice) as $installment) {
            if ($installment['status'] !== 'PAID') {
                return false;
            }
        }

        return true;
    }

    /**
     * Idempotent: reuses an already-open PENDING payment for the same
     * installment+method instead of minting a new one on every retry/click.
     */
    private function getOrCreatePendingPayment(array $invoice, int $installmentNumber, string $method): array
    {
        if (Payment::isInstallmentPaid($invoice['id'], $installmentNumber)) {
            throw new \RuntimeException('این قسط قبلاً پرداخت شده است.');
        }

        $existing = Payment::findPendingForInstallment($invoice['id'], $installmentNumber, $method);

        if ($existing !== null) {
            return $existing;
        }

        $plan = $this->installmentPlan($invoice);
        $amount = $plan[$installmentNumber - 1]['amount'] ?? null;

        if ($amount === null) {
            throw new \InvalidArgumentException('شماره قسط نامعتبر است.');
        }

        $id = Payment::insert([
            'company_id' => $invoice['company_id'],
            'cohort_id' => $invoice['cohort_id'],
            'invoice_id' => $invoice['id'],
            'amount' => $amount,
            'installment_number' => $installmentNumber,
            'method' => $method,
            'status' => 'PENDING',
            'payment_intent_id' => Model::uuid(),
        ]);

        return Payment::find($id);
    }

    /** @return array{gateway_url: string, payment_id: string} */
    public function startZarinpalPayment(array $invoice, int $installmentNumber, string $callbackUrl, ?string $mobile, ?string $email): array
    {
        $payment = $this->getOrCreatePendingPayment($invoice, $installmentNumber, 'ZARINPAL');

        // Reuse a still-valid authority instead of opening a second gateway transaction for the same intent.
        if (!empty($payment['transaction_id'])) {
            return ['gateway_url' => $this->zarinpal->gatewayUrl($payment['transaction_id']), 'payment_id' => $payment['id']];
        }

        $result = $this->zarinpal->request(
            (int) $payment['amount'],
            'پرداخت قسط ' . $installmentNumber . ' شهریه برنامه شتاب‌دهنده',
            $callbackUrl . '?payment_id=' . $payment['id'],
            $mobile,
            $email
        );

        Payment::update($payment['id'], [
            'payment_gateway' => 'zarinpal',
            'transaction_id' => $result['authority'],
        ]);

        AuditLog::record('payment.zarinpal_initiated', 'Payment', $payment['id'], null, ['authority' => $result['authority']]);

        return ['gateway_url' => $result['gateway_url'], 'payment_id' => $payment['id']];
    }

    public function verifyZarinpalCallback(string $paymentId, string $authority, string $gatewayStatus): array
    {
        $payment = Payment::find($paymentId);

        if ($payment === null || $payment['method'] !== 'ZARINPAL' || $payment['transaction_id'] !== $authority) {
            throw new \RuntimeException('تراکنش نامعتبر است.');
        }

        if ($payment['status'] === 'PAID') {
            return $payment; // already verified - idempotent callback retry
        }

        if ($gatewayStatus !== 'OK') {
            Payment::update($paymentId, ['status' => 'FAILED']);
            AuditLog::record('payment.zarinpal_failed', 'Payment', $paymentId);

            return Payment::find($paymentId);
        }

        $result = $this->zarinpal->verify((int) $payment['amount'], $authority);

        Payment::update($paymentId, [
            'status' => 'PAID',
            'transaction_id' => $result['ref_id'],
            'paid_at' => gmdate('Y-m-d H:i:s'),
        ]);

        AuditLog::record('payment.verified', 'Payment', $paymentId, null, ['ref_id' => $result['ref_id']]);
        $this->notifyOwnerPaymentReceived($payment);

        return Payment::find($paymentId);
    }

    /**
     * @param array{name: string, tmp_name: string, size: int, error: int} $file
     */
    public function submitManualBankTransfer(
        array $invoice,
        int $installmentNumber,
        string $uploadedByUserId,
        string $trackingNumber,
        string $transferDateJalali,
        array $file
    ): array {
        $payment = $this->getOrCreatePendingPayment($invoice, $installmentNumber, 'MANUAL_BANK_TRANSFER');

        if (!empty($payment['bank_tracking_number'])) {
            // Already submitted for this intent - idempotent re-submission guard.
            return $payment;
        }

        $image = $this->storeReceiptImage($uploadedByUserId, $file);

        Payment::update($payment['id'], [
            'bank_tracking_number' => $trackingNumber,
            'bank_transfer_date' => \App\Lib\DateConverter::fromJalali($transferDateJalali),
            'manual_receipt_image_id' => $image['id'],
        ]);

        AuditLog::record('payment.manual_submitted', 'Payment', $payment['id'], null, ['tracking_number' => $trackingNumber]);

        $adminRole = Role::findByName(Constants::ROLE_ADMIN);
        if ($adminRole !== null) {
            Notifier::sendToRole('payment.manual_submitted', $adminRole['id'], 'فیش واریزی جدید برای تایید', 'یک فیش واریز بانکی جدید در انتظار تایید است.', '/admin/payments');
        }

        return Payment::find($payment['id']);
    }

    /** @param array{name: string, tmp_name: string, size: int, error: int} $file */
    private function storeReceiptImage(string $uploadedByUserId, array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('بارگذاری تصویر فیش با خطا مواجه شد.');
        }

        if (!is_uploaded_file($file['tmp_name']) && php_sapi_name() !== 'cli') {
            throw new \RuntimeException('فایل نامعتبر است.');
        }

        $extension = FileUploader::detectExtension($file['name']);

        if (!in_array($extension, self::RECEIPT_ALLOWED_EXTENSIONS, true) || !FileUploader::isAllowedExtension($extension)) {
            throw new \InvalidArgumentException('فرمت تصویر فیش باید JPG، PNG یا PDF باشد.');
        }

        if (!FileUploader::magicNumberMatches($file['tmp_name'], $extension)) {
            throw new \InvalidArgumentException('محتوای فایل با پسوند اعلام‌شده مطابقت ندارد.');
        }

        $plaintext = file_get_contents($file['tmp_name']);
        $encrypted = FileEncryptor::encrypt($plaintext);

        $dir = rtrim($_ENV['STORAGE_PATH'] ?? sys_get_temp_dir(), '/') . '/receipts';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.enc';
        file_put_contents($dir . '/' . $storedName, $encrypted['ciphertext']);

        $id = Image::insert([
            'file_name' => basename($file['name']),
            'file_type' => $extension,
            'file_size' => $file['size'],
            'file_path' => "receipts/{$storedName}",
            'encryption_iv' => $encrypted['iv'],
            'checksum' => hash('sha256', $plaintext),
            'uploaded_by_id' => $uploadedByUserId,
        ]);

        return Image::find($id);
    }

    public function approveManualPayment(string $paymentId, string $adminUserId): array
    {
        $payment = Payment::find($paymentId);

        if ($payment === null || $payment['status'] !== 'PENDING') {
            throw new \RuntimeException('این پرداخت قابل تایید نیست.');
        }

        Payment::update($paymentId, [
            'status' => 'PAID',
            'paid_at' => gmdate('Y-m-d H:i:s'),
            'reviewed_by_id' => $adminUserId,
            'reviewed_at' => gmdate('Y-m-d H:i:s'),
        ]);

        AuditLog::record('payment.approved', 'Payment', $paymentId, null, null, $adminUserId);
        $this->notifyOwnerPaymentReceived(Payment::find($paymentId));

        return Payment::find($paymentId);
    }

    public function rejectManualPayment(string $paymentId, string $adminUserId, string $reason): array
    {
        $payment = Payment::find($paymentId);

        if ($payment === null || $payment['status'] !== 'PENDING') {
            throw new \RuntimeException('این پرداخت قابل رد کردن نیست.');
        }

        Payment::update($paymentId, [
            'status' => 'FAILED',
            'reviewed_by_id' => $adminUserId,
            'reviewed_at' => gmdate('Y-m-d H:i:s'),
            'rejected_reason' => $reason,
        ]);

        AuditLog::record('payment.rejected', 'Payment', $paymentId, null, ['reason' => $reason], $adminUserId);

        $owner = Company::find($payment['company_id'])['owner_user_id'] ?? null;
        if ($owner !== null) {
            Notifier::send('payment.rejected', $owner, 'فیش واریزی رد شد', 'فیش واریزی ارسالی شما تایید نشد: ' . $reason, '/payment');
        }

        return Payment::find($paymentId);
    }

    private function notifyOwnerPaymentReceived(array $payment): void
    {
        $owner = Company::find($payment['company_id'])['owner_user_id'] ?? null;
        if ($owner !== null) {
            Notifier::send('payment.received', $owner, 'پرداخت با موفقیت ثبت شد', 'قسط شماره ' . $payment['installment_number'] . ' با موفقیت پرداخت شد.', '/payment');
        }
    }
}
