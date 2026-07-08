<?php

declare(strict_types=1);

namespace App\Features\Task\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Tenant;
use App\Features\Task\Services\TaskService;
use App\Lib\DateConverter;

final class TaskController extends Controller
{
    private TaskService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new TaskService();
    }

    public function index(): void
    {
        $companyId = Tenant::companyId();

        $this->render('Task::board', [
            'board' => $this->service->board($companyId),
            'status' => Session::flash('status'),
            'error' => Session::flash('error'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();
        $data = Request::post();
        $data['assigned_to_user_id'] = Auth::id();

        if ($data['due_date'] ?? '') {
            $data['due_date'] = DateConverter::fromJalali($data['due_date']);
        } else {
            $data['due_date'] = null;
        }

        if (trim($data['title'] ?? '') === '') {
            Session::flash('error', 'عنوان وظیفه الزامی است.');
            $this->redirect('/tasks');

            return;
        }

        $this->service->create($companyId, $data);
        $this->redirect('/tasks');
    }

    public function updateStatus(string $id): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();
        $status = (string) Request::input('status', '');

        try {
            $task = $this->service->updateStatus($id, $companyId, $status);
            Response::success(['task' => ['id' => $task['id'], 'status' => $task['status']]]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function delete(string $id): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();

        try {
            $this->service->delete($id, $companyId);
            Session::flash('status', 'وظیفه حذف شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/tasks');
    }
}
