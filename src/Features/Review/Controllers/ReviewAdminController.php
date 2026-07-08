<?php

declare(strict_types=1);

namespace App\Features\Review\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Features\Application\Models\Application;
use App\Features\Auth\Models\User;
use App\Features\Cohort\Models\Cohort;
use App\Features\Company\Models\Company;
use App\Features\Document\Models\Document;
use App\Features\Document\Services\DocumentService;
use App\Features\Review\Models\ApplicationClarification;
use App\Features\Review\Models\ApplicationReviewer;
use App\Features\Review\Services\ReviewService;

final class ReviewAdminController extends Controller
{
    private ReviewService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ReviewService();
    }

    public function index(): void
    {
        $applications = Application::byStatuses(['SUBMITTED', 'UNDER_REVIEW', 'PENDING_INFO']);

        $rows = array_map(function (array $app) {
            $app['company'] = Company::find($app['company_id']);
            $app['cohort'] = Cohort::find($app['cohort_id']);
            $app['reviewer_count'] = count(ApplicationReviewer::forApplication($app['id']));

            return $app;
        }, $applications);

        $this->render('Review::admin-queue', [
            'applications' => $rows,
            'reviewers' => $this->service->availableReviewers(),
        ]);
    }

    public function show(string $id): void
    {
        $application = Application::find($id);

        if ($application === null) {
            $this->redirect('/admin/reviews');

            return;
        }

        $summary = $this->service->scoreSummary($id);
        $assignedReviewerIds = array_column($summary['scores'], 'reviewer_user_id');

        $reviewers = array_map(static function (array $r) use ($assignedReviewerIds) {
            $r['is_assigned'] = in_array($r['id'], $assignedReviewerIds, true);

            return $r;
        }, $this->service->availableReviewers());

        $scoreRows = array_map(static function (array $s) {
            $s['reviewer'] = User::find($s['reviewer_user_id']);

            return $s;
        }, $summary['scores']);

        $documentService = new DocumentService();
        $documents = array_map(static function (array $d) use ($documentService) {
            $d['download_url'] = $documentService->signedDownloadUrl($d['id']);

            return $d;
        }, Document::forCompany($application['company_id']));

        $this->render('Review::admin-application', [
            'application' => $application,
            'company' => Company::find($application['company_id']),
            'cohort' => Cohort::find($application['cohort_id']),
            'scoreRows' => $scoreRows,
            'average' => $summary['average'],
            'hasVariance' => $summary['hasVariance'],
            'reviewers' => $reviewers,
            'clarifications' => ApplicationClarification::forApplication($id),
            'documents' => $documents,
        ]);
    }

    public function assign(string $id): void
    {
        $this->requireCsrf();

        $reviewerUserId = (string) Request::input('reviewer_user_id', '');

        try {
            $this->service->assignReviewer($id, $reviewerUserId);
            Session::flash('status', 'داور با موفقیت تخصیص یافت.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/admin/reviews/' . $id);
    }

    public function unassign(string $id, string $reviewerUserId): void
    {
        $this->requireCsrf();

        $this->service->unassignReviewer($id, $reviewerUserId);
        Session::flash('status', 'تخصیص داور لغو شد.');
        $this->redirect('/admin/reviews/' . $id);
    }

    public function override(string $id): void
    {
        $this->requireCsrf();

        $status = (string) Request::input('status', '');
        $reason = (string) Request::input('reason', '');

        try {
            $this->service->manualOverride($id, $status, $reason, \App\Core\Auth::id());
            Session::flash('status', 'وضعیت درخواست به‌صورت دستی تغییر یافت.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/admin/reviews/' . $id);
    }
}
