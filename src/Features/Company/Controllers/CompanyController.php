<?php

declare(strict_types=1);

namespace App\Features\Company\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Tenant;
use App\Features\Company\Models\Company;
use App\Features\Company\Services\CompanyService;
use App\Lib\HsCodes;
use App\Lib\Validator;

final class CompanyController extends Controller
{
    private CompanyService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CompanyService();
    }

    private function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'registration_number' => 'max:255',
            'industry' => 'max:255',
            'country' => 'max:255',
            'city' => 'max:255',
            'website' => 'max:2048',
        ];
    }

    public function showCreate(): void
    {
        if (Tenant::hasCompany()) {
            $this->redirect('/company');

            return;
        }

        $this->render('Company::create', ['errors' => [], 'old' => [], 'hsTree' => HsCodes::tree()]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        if (Tenant::hasCompany()) {
            $this->redirect('/company');

            return;
        }

        $data = Request::post();
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            $this->render('Company::create', ['errors' => $validator->errors(), 'old' => $data, 'hsTree' => HsCodes::tree()]);

            return;
        }

        $data['hs_codes'] = $this->selectedHsCodes();

        $this->service->create(Auth::id(), $data);
        Tenant::reset();

        $this->redirect('/company');
    }

    public function show(): void
    {
        $company = Tenant::company();

        if ($company === null) {
            $this->redirect('/company/create');

            return;
        }

        $this->render('Company::show', ['company' => $company, 'hsCodes' => CompanyService::hsCodes($company), 'hsFlat' => HsCodes::flat()]);
    }

    public function showEdit(): void
    {
        $company = Tenant::company();

        if ($company === null) {
            $this->redirect('/company/create');

            return;
        }

        $this->render('Company::edit', [
            'company' => $company,
            'selected' => CompanyService::hsCodes($company),
            'hsTree' => HsCodes::tree(),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireCsrf();

        $company = Tenant::company();

        if ($company === null) {
            $this->redirect('/company/create');

            return;
        }

        $data = Request::post();
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            $this->render('Company::edit', [
                'company' => $company,
                'selected' => $this->selectedHsCodes(),
                'hsTree' => HsCodes::tree(),
                'errors' => $validator->errors(),
            ]);

            return;
        }

        $data['hs_codes'] = $this->selectedHsCodes();

        $this->service->update($company['id'], $data);
        Tenant::reset();

        $this->redirect('/company');
    }

    /** @return array<int, string> */
    private function selectedHsCodes(): array
    {
        $raw = $_POST['hs_codes'] ?? [];
        $raw = is_array($raw) ? $raw : [];

        return array_values(array_filter($raw, [HsCodes::class, 'isValidCode']));
    }
}
