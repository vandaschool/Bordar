<?php

declare(strict_types=1);

namespace App\Features\Company\Services;

use App\Core\AuditLog;
use App\Features\Company\Models\Company;

final class CompanyService
{
    /** @param array<string, mixed> $data */
    public function create(string $ownerUserId, array $data): array
    {
        $id = Company::insert([
            'name' => $data['name'],
            'owner_user_id' => $ownerUserId,
            'registration_number' => $data['registration_number'] !== '' ? $data['registration_number'] : null,
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'website' => $data['website'] !== '' ? $data['website'] : null,
            'description' => $data['description'] ?? null,
            'hs_codes' => json_encode($data['hs_codes'] ?? [], JSON_UNESCAPED_UNICODE),
            'status' => 'PENDING',
        ]);

        AuditLog::record('company.created', 'Company', $id, null, ['name' => $data['name']]);

        return Company::find($id);
    }

    /** @param array<string, mixed> $data */
    public function update(string $companyId, array $data): array
    {
        $before = Company::find($companyId);

        $payload = [
            'name' => $data['name'],
            'registration_number' => $data['registration_number'] !== '' ? $data['registration_number'] : null,
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'website' => $data['website'] !== '' ? $data['website'] : null,
            'description' => $data['description'] ?? null,
        ];

        if (array_key_exists('hs_codes', $data)) {
            $payload['hs_codes'] = json_encode($data['hs_codes'], JSON_UNESCAPED_UNICODE);
        }

        Company::update($companyId, $payload);

        AuditLog::record('company.updated', 'Company', $companyId, $before, $payload);

        return Company::find($companyId);
    }

    /** @return array<int, string> */
    public static function hsCodes(array $company): array
    {
        $decoded = json_decode($company['hs_codes'] ?? '[]', true);

        return is_array($decoded) ? $decoded : [];
    }
}
