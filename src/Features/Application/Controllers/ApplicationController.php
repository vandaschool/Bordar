<?php

declare(strict_types=1);

namespace App\Features\Application\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Tenant;
use App\Features\Application\Models\Application;
use App\Features\Application\Services\ApplicationService;
use App\Features\Cohort\Models\Cohort;

final class ApplicationController extends Controller
{
    private ApplicationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ApplicationService();
    }

    public function index(): void
    {
        $companyId = Tenant::companyId();
        $applications = $companyId !== null ? Application::forCompany($companyId) : [];

        $cohortNames = [];
        foreach ($applications as $app) {
            if (!isset($cohortNames[$app['cohort_id']])) {
                $cohort = Cohort::find($app['cohort_id']);
                $cohortNames[$app['cohort_id']] = $cohort['name'] ?? '-';
            }
        }

        $this->render('Application::index', ['applications' => $applications, 'cohortNames' => $cohortNames]);
    }

    public function showStart(): void
    {
        $this->render('Application::choose-cohort', ['cohorts' => Cohort::openForApplications()]);
    }

    public function start(): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();
        $cohortId = (string) Request::input('cohort_id', '');
        $cohort = $cohortId !== '' ? Cohort::find($cohortId) : null;

        if ($companyId === null || $cohort === null) {
            Session::flash('error', 'کوهورت انتخاب‌شده معتبر نیست.');
            $this->redirect('/applications/start');

            return;
        }

        $application = $this->service->startOrResumeDraft($companyId, $cohortId);

        $this->redirect('/applications/' . $application['id']);
    }

    private function loadScoped(string $id): ?array
    {
        $companyId = Tenant::companyId();

        if (Auth::hasRole('Admin')) {
            return Application::find($id);
        }

        if ($companyId === null) {
            return null;
        }

        return Application::findScoped($id, $companyId);
    }

    public function show(string $id): void
    {
        $application = $this->loadScoped($id);

        if ($application === null) {
            http_response_code(404);
            $this->redirect('/applications');

            return;
        }

        $cohort = Cohort::find($application['cohort_id']);
        $data = json_decode($application['data'], true) ?: [];
        $missing = $this->service->missingFields($application);

        $this->render('Application::form', [
            'application' => $application,
            'cohort' => $cohort,
            'data' => $data,
            'missing' => $missing,
            'readOnly' => $application['status'] !== 'DRAFT',
        ]);
    }

    public function autosave(string $id): void
    {
        $this->requireCsrf();

        $application = $this->loadScoped($id);

        if ($application === null) {
            Response::error('درخواست یافت نشد.', 404);
        }

        $fields = Request::isJson() ? Request::json() : Request::post();

        try {
            $result = $this->service->autosave($application, $fields);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 422);
        }

        Response::success($result, 'ذخیره شد.');
    }

    public function submit(string $id): void
    {
        $this->requireCsrf();

        $application = $this->loadScoped($id);

        if ($application === null) {
            $this->redirect('/applications');

            return;
        }

        try {
            $this->service->submit($application);
            Session::flash('status', 'درخواست شما با موفقیت ارسال شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/applications/' . $id);
    }
}
