<?php

declare(strict_types=1);

namespace App\Features\Payment\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Features\Company\Models\Company;
use App\Features\Payment\Models\Image;
use App\Features\Payment\Models\Payment;
use App\Features\Payment\Services\PaymentService;
use App\Lib\FileEncryptor;

final class PaymentAdminController extends Controller
{
    private PaymentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PaymentService();
    }

    public function index(): void
    {
        $pending = Payment::pendingManualTransfers();
        $companies = Company::findMany(array_column($pending, 'company_id'));
        $images = Image::findMany(array_column($pending, 'manual_receipt_image_id'));

        $payments = array_map(static function (array $p) use ($companies, $images) {
            $p['company'] = $companies[$p['company_id']] ?? null;
            $p['receipt_image'] = $images[$p['manual_receipt_image_id']] ?? null;

            return $p;
        }, $pending);

        $this->render('Payment::admin-index', [
            'payments' => $payments,
            'status' => Session::flash('status'),
            'error' => Session::flash('error'),
        ]);
    }

    public function approve(string $id): void
    {
        $this->requireCsrf();

        try {
            $this->service->approveManualPayment($id, Auth::id());
            Session::flash('status', 'پرداخت تایید شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/admin/payments');
    }

    public function receipt(string $paymentId): void
    {
        $payment = Payment::find($paymentId);
        $image = $payment !== null && $payment['manual_receipt_image_id'] !== null ? Image::find($payment['manual_receipt_image_id']) : null;

        if ($image === null) {
            http_response_code(404);

            return;
        }

        $storagePath = rtrim($_ENV['STORAGE_PATH'] ?? sys_get_temp_dir(), '/') . '/' . $image['file_path'];
        $ciphertext = file_get_contents($storagePath);
        $plaintext = FileEncryptor::decrypt($ciphertext, $image['encryption_iv']);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: inline; filename="' . rawurlencode($image['file_name']) . '"');
        echo $plaintext;
    }

    public function reject(string $id): void
    {
        $this->requireCsrf();

        $reason = (string) Request::input('reason', '');

        try {
            $this->service->rejectManualPayment($id, Auth::id(), $reason);
            Session::flash('status', 'پرداخت رد شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/admin/payments');
    }
}
