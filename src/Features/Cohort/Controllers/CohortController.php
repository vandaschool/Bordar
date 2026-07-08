<?php

declare(strict_types=1);

namespace App\Features\Cohort\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Features\Cohort\Models\Cohort;
use App\Features\Cohort\Services\CohortService;
use App\Lib\DateConverter;
use App\Lib\Validator;

final class CohortController extends Controller
{
    private CohortService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CohortService();
    }

    private function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'start_date' => 'required',
            'end_date' => 'required',
        ];
    }

    public function index(): void
    {
        $this->render('Cohort::admin-index', ['cohorts' => Cohort::allOrderedByStartDate()]);
    }

    public function showCreate(): void
    {
        $this->render('Cohort::admin-form', ['cohort' => null, 'errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = Request::post();
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            $this->render('Cohort::admin-form', ['cohort' => null, 'errors' => $validator->errors(), 'old' => $data]);

            return;
        }

        $this->service->create($data);
        $this->redirect('/admin/cohorts');
    }

    public function showEdit(string $id): void
    {
        $cohort = Cohort::find($id);

        if ($cohort === null) {
            $this->redirect('/admin/cohorts');

            return;
        }

        $old = [
            'name' => $cohort['name'],
            'start_date' => DateConverter::toJalaliDate($cohort['start_date']),
            'end_date' => DateConverter::toJalaliDate($cohort['end_date']),
            'application_deadline' => DateConverter::toJalaliDate($cohort['application_deadline']),
            'status' => $cohort['status'],
            'description' => $cohort['description'],
        ];

        $this->render('Cohort::admin-form', ['cohort' => $cohort, 'errors' => [], 'old' => $old]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();

        $cohort = Cohort::find($id);

        if ($cohort === null) {
            $this->redirect('/admin/cohorts');

            return;
        }

        $data = Request::post();
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            $this->render('Cohort::admin-form', ['cohort' => $cohort, 'errors' => $validator->errors(), 'old' => $data]);

            return;
        }

        $this->service->update($id, $data);
        $this->redirect('/admin/cohorts');
    }
}
