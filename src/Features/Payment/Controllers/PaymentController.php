<?php

declare(strict_types=1);

namespace App\Features\Payment\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Tenant;
use App\Features\Cohort\Models\Cohort;
use App\Features\Cohort\Models\CompanyCohort;
use App\Features\Payment\Models\Payment;
use App\Features\Payment\Services\PaymentService;

final class PaymentController extends Controller
{
    private PaymentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PaymentService();
    }

    private function callbackUrl(): string
    {
        return \App\Config\App::url() . '/payment/callback';
    }

    public function index(): void
    {
        $companyId = Tenant::companyId();
        $accepted = $companyId !== null ? CompanyCohort::latestAcceptedForCompany($companyId) : null;

        if ($accepted === null) {
            $this->render('Payment::not-eligible', []);

            return;
        }

        $installmentCount = (int) Request::input('installments', 1);
        $invoice = $this->service->getOrCreateInvoice($companyId, $accepted['cohort_id'], $installmentCount, Auth::id());

        $this->render('Payment::index', [
            'invoice' => $invoice,
            'cohort' => Cohort::find($accepted['cohort_id']),
            'plan' => $this->service->installmentPlan($invoice),
            'isFullyPaid' => $this->service->isFullyPaid($invoice),
            'status' => Session::flash('status'),
            'error' => Session::flash('error'),
        ]);
    }

    private function currentInvoiceOrFail(): ?array
    {
        $companyId = Tenant::companyId();
        $accepted = $companyId !== null ? CompanyCohort::latestAcceptedForCompany($companyId) : null;

        if ($accepted === null) {
            return null;
        }

        return $this->service->getOrCreateInvoice($companyId, $accepted['cohort_id']);
    }

    public function payViaZarinpal(string $installmentNumber): void
    {
        $this->requireCsrf();

        $invoice = $this->currentInvoiceOrFail();

        if ($invoice === null) {
            $this->redirect('/payment');

            return;
        }

        $user = Auth::user();

        try {
            $result = $this->service->startZarinpalPayment($invoice, (int) $installmentNumber, $this->callbackUrl(), $user['phone_number'] ?? null, $user['email'] ?? null);
            $this->redirect($result['gateway_url']);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/payment');
        }
    }

    public function zarinpalCallback(): void
    {
        $paymentId = (string) Request::input('payment_id', '');
        $authority = (string) Request::input('Authority', '');
        $status = (string) Request::input('Status', '');

        try {
            $payment = $this->service->verifyZarinpalCallback($paymentId, $authority, $status);
            Session::flash($payment['status'] === 'PAID' ? 'status' : 'error', $payment['status'] === 'PAID'
                ? 'پرداخت با موفقیت انجام شد.'
                : 'پرداخت ناموفق بود یا توسط شما لغو شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/payment');
    }

    public function submitManualTransfer(string $installmentNumber): void
    {
        $this->requireCsrf();

        $invoice = $this->currentInvoiceOrFail();

        if ($invoice === null) {
            $this->redirect('/payment');

            return;
        }

        try {
            $this->service->submitManualBankTransfer(
                $invoice,
                (int) $installmentNumber,
                Auth::id(),
                (string) Request::input('tracking_number', ''),
                (string) Request::input('transfer_date', ''),
                $_FILES['receipt'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'tmp_name' => '', 'size' => 0]
            );
            Session::flash('status', 'فیش واریزی شما ثبت شد و در انتظار تایید ادمین است.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/payment');
    }
}
