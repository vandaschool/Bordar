<?php

declare(strict_types=1);

namespace App\Features\Review\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Features\Application\Models\Application;
use App\Features\Cohort\Models\Cohort;
use App\Features\Company\Models\Company;
use App\Features\Document\Models\Document;
use App\Features\Document\Services\DocumentService;
use App\Features\Review\Models\ApplicationClarification;
use App\Features\Review\Models\ApplicationReviewer;
use App\Features\Review\Services\ReviewService;

final class ReviewerController extends Controller
{
    private ReviewService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ReviewService();
    }

    public function queue(): void
    {
        $assignments = ApplicationReviewer::forReviewer(Auth::id());

        $rows = array_map(function (array $a) {
            $application = Application::find($a['application_id']);
            $a['application'] = $application;
            $a['company'] = $application !== null ? Company::find($application['company_id']) : null;
            $a['cohort'] = $application !== null ? Cohort::find($application['cohort_id']) : null;

            return $a;
        }, $assignments);

        $this->render('Review::reviewer-queue', ['rows' => $rows]);
    }

    private function loadAssignment(string $applicationId): ?array
    {
        return ApplicationReviewer::find($applicationId, Auth::id());
    }

    public function show(string $id): void
    {
        $assignment = $this->loadAssignment($id);
        $application = Application::find($id);

        if ($assignment === null || $application === null) {
            http_response_code(404);
            $this->redirect('/reviewer/queue');

            return;
        }

        $data = json_decode($application['data'], true) ?: [];

        $documentService = new DocumentService();
        $documents = array_map(static function (array $d) use ($documentService) {
            $d['download_url'] = $documentService->signedDownloadUrl($d['id']);

            return $d;
        }, Document::forCompany($application['company_id']));

        $this->render('Review::reviewer-application', [
            'application' => $application,
            'assignment' => $assignment,
            'company' => Company::find($application['company_id']),
            'cohort' => Cohort::find($application['cohort_id']),
            'data' => $data,
            'clarifications' => ApplicationClarification::forApplication($id),
            'documents' => $documents,
        ]);
    }

    public function saveDraft(string $id): void
    {
        $this->requireCsrf();

        $score = Request::input('score', '');
        $feedback = (string) Request::input('feedback', '');

        try {
            $this->service->saveDraft($id, Auth::id(), $score === '' ? null : (float) $score, $feedback);
            Session::flash('status', 'پیش‌نویس ذخیره شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/reviewer/applications/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requireCsrf();

        $score = Request::input('score', '');
        $feedback = (string) Request::input('feedback', '');

        try {
            $this->service->submitReview($id, Auth::id(), $score === '' ? null : (float) $score, $feedback);
            Session::flash('status', 'امتیازدهی شما ثبت و قفل شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/reviewer/applications/' . $id);
    }

    public function toggleConflict(string $id): void
    {
        $this->requireCsrf();

        $assignment = $this->loadAssignment($id);

        if ($assignment !== null) {
            $this->service->flagConflictOfInterest($id, Auth::id(), !$assignment['conflict_of_interest']);
        }

        $this->redirect('/reviewer/applications/' . $id);
    }

    public function requestClarification(string $id): void
    {
        $this->requireCsrf();

        $question = (string) Request::input('question', '');

        if (trim($question) === '') {
            Session::flash('error', 'متن سوال را وارد کنید.');
            $this->redirect('/reviewer/applications/' . $id);

            return;
        }

        $this->service->requestClarification($id, Auth::id(), $question);
        Session::flash('status', 'درخواست شفاف‌سازی برای متقاضی ارسال شد.');
        $this->redirect('/reviewer/applications/' . $id);
    }
}
