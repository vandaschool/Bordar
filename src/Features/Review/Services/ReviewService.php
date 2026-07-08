<?php

declare(strict_types=1);

namespace App\Features\Review\Services;

use App\Config\Constants;
use App\Core\AuditLog;
use App\Features\Application\Models\Application;
use App\Features\Auth\Models\Role;
use App\Features\Cohort\Models\CompanyCohort;
use App\Features\Auth\Models\User;
use App\Features\Review\Models\ApplicationClarification;
use App\Features\Review\Models\ApplicationReviewer;

final class ReviewService
{
    /** Score gap between reviewers, at or above which the admin dashboard flags a discrepancy for manual resolution. */
    private const SCORE_VARIANCE_THRESHOLD = 3.0;

    /** @return array<int, array<string, mixed>> Reviewer users with their current active workload. */
    public function availableReviewers(): array
    {
        $role = Role::findByName(Constants::ROLE_REVIEWER);

        if ($role === null) {
            return [];
        }

        $reviewers = User::where('role_id', $role['id']);

        foreach ($reviewers as &$reviewer) {
            $reviewer['active_workload'] = ApplicationReviewer::activeWorkloadCount($reviewer['id']);
        }

        return $reviewers;
    }

    public function assignReviewer(string $applicationId, string $reviewerUserId): void
    {
        if (ApplicationReviewer::find($applicationId, $reviewerUserId) !== null) {
            throw new \RuntimeException('این داور قبلاً به این درخواست تخصیص یافته است.');
        }

        ApplicationReviewer::create($applicationId, $reviewerUserId);

        $application = Application::find($applicationId);
        if ($application !== null && $application['status'] === 'SUBMITTED') {
            Application::update($applicationId, ['status' => 'UNDER_REVIEW']);
        }

        AuditLog::record('review.reviewer_assigned', 'Application', $applicationId, null, ['reviewer_user_id' => $reviewerUserId]);
    }

    public function unassignReviewer(string $applicationId, string $reviewerUserId): void
    {
        ApplicationReviewer::softDelete($applicationId, $reviewerUserId);
        AuditLog::record('review.reviewer_unassigned', 'Application', $applicationId, null, ['reviewer_user_id' => $reviewerUserId]);
    }

    public function flagConflictOfInterest(string $applicationId, string $reviewerUserId, bool $flag): void
    {
        ApplicationReviewer::update($applicationId, $reviewerUserId, ['conflict_of_interest' => $flag ? 1 : 0]);
        AuditLog::record('review.conflict_of_interest', 'Application', $applicationId, null, ['reviewer_user_id' => $reviewerUserId, 'flag' => $flag]);
    }

    /** @param float|null $score 0-100 */
    public function saveDraft(string $applicationId, string $reviewerUserId, ?float $score, string $feedback): void
    {
        $assignment = ApplicationReviewer::find($applicationId, $reviewerUserId);

        if ($assignment === null) {
            throw new \RuntimeException('این درخواست به شما تخصیص نیافته است.');
        }

        if ($assignment['status'] === 'SUBMITTED') {
            throw new \RuntimeException('امتیازدهی شما قفل شده و دیگر قابل ویرایش نیست.');
        }

        ApplicationReviewer::update($applicationId, $reviewerUserId, [
            'status' => 'DRAFT',
            'score' => $score,
            'feedback' => $feedback,
        ]);
    }

    public function submitReview(string $applicationId, string $reviewerUserId, ?float $score, string $feedback): void
    {
        if ($score === null || $score < 0 || $score > 100) {
            throw new \InvalidArgumentException('امتیاز باید عددی بین ۰ تا ۱۰۰ باشد.');
        }

        $assignment = ApplicationReviewer::find($applicationId, $reviewerUserId);

        if ($assignment === null) {
            throw new \RuntimeException('این درخواست به شما تخصیص نیافته است.');
        }

        if ($assignment['status'] === 'SUBMITTED') {
            throw new \RuntimeException('امتیازدهی شما قبلاً ثبت و قفل شده است.');
        }

        ApplicationReviewer::update($applicationId, $reviewerUserId, [
            'status' => 'SUBMITTED',
            'score' => $score,
            'feedback' => $feedback,
            'completed_at' => gmdate('Y-m-d H:i:s'),
        ]);

        AuditLog::record('review.submitted', 'Application', $applicationId, null, ['reviewer_user_id' => $reviewerUserId, 'score' => $score]);
    }

    public function requestClarification(string $applicationId, string $reviewerUserId, string $question): void
    {
        ApplicationClarification::insert([
            'application_id' => $applicationId,
            'reviewer_user_id' => $reviewerUserId,
            'question' => $question,
        ]);

        Application::update($applicationId, ['status' => 'PENDING_INFO']);

        AuditLog::record('review.clarification_requested', 'Application', $applicationId, null, ['reviewer_user_id' => $reviewerUserId]);
    }

    public function respondClarification(string $clarificationId, string $response): void
    {
        $clarification = ApplicationClarification::find($clarificationId);

        if ($clarification === null || $clarification['response'] !== null) {
            throw new \RuntimeException('این درخواست شفاف‌سازی معتبر نیست یا قبلاً پاسخ داده شده.');
        }

        ApplicationClarification::update($clarificationId, [
            'response' => $response,
            'responded_at' => gmdate('Y-m-d H:i:s'),
        ]);

        $applicationId = $clarification['application_id'];

        if (!ApplicationClarification::hasPending($applicationId)) {
            Application::update($applicationId, ['status' => 'UNDER_REVIEW']);
        }

        AuditLog::record('review.clarification_responded', 'Application', $applicationId, null, ['clarification_id' => $clarificationId]);
    }

    /** @return array{scores: array<int, array<string, mixed>>, average: ?float, hasVariance: bool} */
    public function scoreSummary(string $applicationId): array
    {
        $assignments = ApplicationReviewer::forApplication($applicationId);
        $submitted = array_values(array_filter($assignments, static fn ($a) => $a['status'] === 'SUBMITTED' && !$a['conflict_of_interest']));
        $scores = array_map(static fn ($a) => (float) $a['score'], $submitted);

        $average = $scores === [] ? null : array_sum($scores) / count($scores);
        $hasVariance = $scores !== [] && (max($scores) - min($scores)) >= self::SCORE_VARIANCE_THRESHOLD;

        return ['scores' => $assignments, 'average' => $average, 'hasVariance' => $hasVariance];
    }

    public function manualOverride(string $applicationId, string $newStatus, string $reason, string $adminUserId): array
    {
        $allowed = ['ACCEPTED', 'REJECTED', 'UNDER_REVIEW', 'PENDING_INFO'];

        if (!in_array($newStatus, $allowed, true)) {
            throw new \InvalidArgumentException('وضعیت انتخاب‌شده نامعتبر است.');
        }

        $before = Application::find($applicationId);
        Application::update($applicationId, ['status' => $newStatus]);

        if (in_array($newStatus, ['ACCEPTED', 'REJECTED'], true) && $before !== null) {
            CompanyCohort::upsertStatus($before['company_id'], $before['cohort_id'], $newStatus);
        }

        AuditLog::record(
            'application.manual_override',
            'Application',
            $applicationId,
            ['status' => $before['status'] ?? null],
            ['status' => $newStatus, 'reason' => $reason],
            $adminUserId
        );

        return Application::find($applicationId);
    }
}
