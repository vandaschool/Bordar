<?php

declare(strict_types=1);

namespace App\Features\Cohort\Services;

use App\Core\AuditLog;
use App\Features\Cohort\Models\Cohort;
use App\Lib\DateConverter;

final class CohortService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): array
    {
        $id = Cohort::insert([
            'name' => $data['name'],
            'start_date' => DateConverter::fromJalali($data['start_date']),
            'end_date' => DateConverter::fromJalali($data['end_date']),
            'application_deadline' => $data['application_deadline'] !== ''
                ? DateConverter::fromJalali($data['application_deadline'], '23:59:59')
                : null,
            'status' => $data['status'] ?? 'UPCOMING',
            'description' => $data['description'] ?? null,
        ]);

        AuditLog::record('cohort.created', 'Cohort', $id, null, ['name' => $data['name']]);

        return Cohort::find($id);
    }

    /** @param array<string, mixed> $data */
    public function update(string $cohortId, array $data): array
    {
        $before = Cohort::find($cohortId);

        $payload = [
            'name' => $data['name'],
            'start_date' => DateConverter::fromJalali($data['start_date']),
            'end_date' => DateConverter::fromJalali($data['end_date']),
            'application_deadline' => $data['application_deadline'] !== ''
                ? DateConverter::fromJalali($data['application_deadline'], '23:59:59')
                : null,
            'status' => $data['status'] ?? 'UPCOMING',
            'description' => $data['description'] ?? null,
        ];

        Cohort::update($cohortId, $payload);
        AuditLog::record('cohort.updated', 'Cohort', $cohortId, $before, $payload);

        return Cohort::find($cohortId);
    }
}
