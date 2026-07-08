<?php

declare(strict_types=1);

namespace App\Features\Onboarding\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Tenant;
use App\Features\Company\Models\Company;
use App\Features\Onboarding\Services\OnboardingService;

final class OnboardingController extends Controller
{
    private OnboardingService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new OnboardingService();
    }

    public function index(): void
    {
        $companyId = Tenant::companyId();
        $company = $companyId !== null ? Company::find($companyId) : null;

        if ($company === null) {
            $this->redirect('/company/create');

            return;
        }

        $this->service->sendWelcomeSequenceIfNeeded($company);

        $this->render('Onboarding::index', [
            'company' => $company,
            'checklist' => $this->service->checklist($company),
            'progress' => $this->service->progressPercent($company),
            'showTour' => $company['onboarding_tour_completed_at'] === null,
        ]);
    }

    public function toggleItem(): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();
        $itemKey = (string) Request::input('item_key', '');
        $completed = (string) Request::input('completed', '0') === '1';

        if ($companyId !== null) {
            try {
                $this->service->toggleManualItem($companyId, $itemKey, $completed);
            } catch (\Throwable) {
                // Ignore invalid/auto item toggle attempts silently - checklist re-render reflects true state.
            }
        }

        $this->redirect('/onboarding');
    }

    public function completeTour(): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();
        if ($companyId !== null) {
            $this->service->completeTour($companyId);
        }

        $this->redirect('/onboarding');
    }
}
