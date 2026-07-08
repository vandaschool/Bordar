<?php

declare(strict_types=1);

namespace App\Features\Application\Services;

use App\Core\AuditLog;
use App\Features\Application\Models\Application;
use App\Lib\Sanitizer;

final class ApplicationService
{
    /** Current shape of the application form; bumped whenever fields are added/removed/renamed. */
    public const FORM_SCHEMA_VERSION = 1;

    /** Fields that must be non-empty before an application can be submitted. */
    private const REQUIRED_FIELDS = [
        'business_overview',
        'export_experience',
        'target_markets',
        'team_size',
        'goals',
    ];

    /** All fields the autosave endpoint is allowed to persist. */
    public const ALLOWED_FIELDS = [
        'business_overview',
        'export_experience',
        'target_markets',
        'annual_revenue',
        'team_size',
        'key_team_members',
        'goals',
        'support_needed',
    ];

    public function startOrResumeDraft(string $companyId, string $cohortId): array
    {
        $existing = Application::activeForCompanyAndCohort($companyId, $cohortId);

        if ($existing !== null) {
            return $existing;
        }

        $id = Application::insert([
            'company_id' => $companyId,
            'cohort_id' => $cohortId,
            'status' => 'DRAFT',
            'data' => json_encode(['_form_version' => self::FORM_SCHEMA_VERSION], JSON_UNESCAPED_UNICODE),
            'version' => 1,
        ]);

        AuditLog::record('application.draft_started', 'Application', $id, null, ['cohort_id' => $cohortId]);

        return Application::find($id);
    }

    /**
     * Merge-saves a partial set of form fields into the JSON data column and
     * bumps the version counter. Only DRAFT applications are editable this
     * way; anything already submitted is locked.
     *
     * @param array<string, mixed> $fields
     * @return array{version: int, saved_at: string}
     */
    public function autosave(array $application, array $fields): array
    {
        if ($application['status'] !== 'DRAFT') {
            throw new \RuntimeException('این درخواست دیگر قابل ویرایش نیست.');
        }

        $data = json_decode($application['data'], true) ?: [];

        foreach (self::ALLOWED_FIELDS as $field) {
            if (array_key_exists($field, $fields)) {
                $value = $fields[$field];
                $data[$field] = is_string($value) ? Sanitizer::string($value) : $value;
            }
        }

        $data['_form_version'] = self::FORM_SCHEMA_VERSION;

        $newVersion = (int) $application['version'] + 1;

        Application::update($application['id'], [
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'version' => $newVersion,
        ]);

        return ['version' => $newVersion, 'saved_at' => gmdate('c')];
    }

    /** @return array<int, string> Missing required field names, empty when ready to submit. */
    public function missingFields(array $application): array
    {
        $data = json_decode($application['data'], true) ?: [];
        $missing = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function submit(array $application): array
    {
        if ($application['status'] !== 'DRAFT') {
            throw new \RuntimeException('این درخواست قبلاً ارسال شده است.');
        }

        $missing = $this->missingFields($application);

        if ($missing !== []) {
            throw new \InvalidArgumentException('لطفاً همه بخش‌های الزامی فرم را تکمیل کنید.');
        }

        Application::update($application['id'], [
            'status' => 'SUBMITTED',
            'submitted_at' => gmdate('Y-m-d H:i:s'),
        ]);

        AuditLog::record('application.submitted', 'Application', $application['id'], ['status' => 'DRAFT'], ['status' => 'SUBMITTED']);

        return Application::find($application['id']);
    }
}
