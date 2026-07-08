<?php

declare(strict_types=1);

namespace App\Features\Onboarding\Services;

use App\Core\AuditLog;
use App\Core\Notifier;
use App\Features\Company\Models\Company;
use App\Features\Document\Models\Document;
use App\Features\Onboarding\Models\OnboardingProgress;
use App\Features\Payment\Models\Invoice;
use App\Features\Payment\Services\PaymentService;

final class OnboardingService
{
    /** Checklist items self-reported by the applicant during their first week, plus a couple derived from real data. */
    public const MANUAL_ITEMS = [
        'join_orientation_webinar' => 'شرکت در وبینار آشنایی با برنامه',
        'book_first_mentor_session' => 'رزرو اولین جلسه منتورینگ',
        'invite_team_members' => 'دعوت اعضای تیم به پلتفرم',
        'explore_lms' => 'آشنایی با مرکز آموزش',
        'read_program_handbook' => 'مطالعه راهنمای برنامه',
    ];

    private const WELCOME_SENT_MARKER = '_welcome_email_sent';

    /** @return array<int, array{key: string, label: string, completed: bool, auto: bool}> */
    public function checklist(array $company): array
    {
        $completed = OnboardingProgress::completedItems($company['id']);
        $items = [];

        $items[] = [
            'key' => 'complete_company_profile',
            'label' => 'تکمیل پروفایل شرکت',
            'completed' => $this->isProfileComplete($company),
            'auto' => true,
        ];

        $items[] = [
            'key' => 'upload_key_documents',
            'label' => 'بارگذاری حداقل یک سند در مخزن اسناد',
            'completed' => Document::forCompany($company['id']) !== [],
            'auto' => true,
        ];

        $items[] = [
            'key' => 'pay_program_fee',
            'label' => 'پرداخت کامل شهریه برنامه',
            'completed' => $this->isFeeFullyPaid($company['id']),
            'auto' => true,
        ];

        foreach (self::MANUAL_ITEMS as $key => $label) {
            $items[] = ['key' => $key, 'label' => $label, 'completed' => array_key_exists($key, $completed), 'auto' => false];
        }

        return $items;
    }

    public function progressPercent(array $company): int
    {
        $items = $this->checklist($company);
        $done = count(array_filter($items, static fn ($i) => $i['completed']));

        return (int) round(($done / count($items)) * 100);
    }

    public function toggleManualItem(string $companyId, string $itemKey, bool $completed): void
    {
        if (!array_key_exists($itemKey, self::MANUAL_ITEMS)) {
            throw new \InvalidArgumentException('این آیتم قابل ویرایش دستی نیست.');
        }

        if ($completed) {
            OnboardingProgress::markComplete($companyId, $itemKey);
        } else {
            OnboardingProgress::markIncomplete($companyId, $itemKey);
        }
    }

    private function isProfileComplete(array $company): bool
    {
        return !empty($company['industry']) && !empty($company['country']) && !empty($company['city']) && !empty($company['description']);
    }

    private function isFeeFullyPaid(string $companyId): bool
    {
        $acceptedCohortInvoice = Invoice::forCompanyAndCohort($companyId, $this->latestCohortId($companyId) ?? '');

        if ($acceptedCohortInvoice === null) {
            return false;
        }

        return (new PaymentService())->isFullyPaid($acceptedCohortInvoice);
    }

    private function latestCohortId(string $companyId): ?string
    {
        $accepted = \App\Features\Cohort\Models\CompanyCohort::latestAcceptedForCompany($companyId);

        return $accepted['cohort_id'] ?? null;
    }

    public function completeTour(string $companyId): void
    {
        Company::update($companyId, ['onboarding_tour_completed_at' => gmdate('Y-m-d H:i:s')]);
        AuditLog::record('onboarding.tour_completed', 'Company', $companyId);
    }

    public function sendWelcomeSequenceIfNeeded(array $company): void
    {
        if (OnboardingProgress::isComplete($company['id'], self::WELCOME_SENT_MARKER)) {
            return;
        }

        OnboardingProgress::markComplete($company['id'], self::WELCOME_SENT_MARKER);

        Notifier::send(
            'onboarding.welcome',
            $company['owner_user_id'],
            'به بردار خوش آمدید!',
            'به تیم بردار خوش آمدید! چک‌لیست هفته اول شما در داشبورد Onboarding آماده است. اولین قدم: تکمیل پروفایل شرکت و بارگذاری اسناد.',
            '/onboarding'
        );
    }
}
