<?php

declare(strict_types=1);

namespace App\Core;

use App\Features\Company\Models\Company;

/**
 * Resolves the company-tenant context for the current request. MVP scope is
 * one company per owning user (companies.owner_user_id), so tenant
 * resolution is derived straight from the authenticated user - no session
 * state to keep in sync. Admins are never implicitly scoped: they must pass
 * an explicit company_id and go through the same *Scoped() query helpers.
 */
final class Tenant
{
    private static ?array $companyCache = null;

    private static bool $resolved = false;

    public static function company(): ?array
    {
        if (self::$resolved) {
            return self::$companyCache;
        }

        self::$resolved = true;
        $userId = Auth::id();

        if ($userId === null) {
            return self::$companyCache = null;
        }

        return self::$companyCache = Company::findByOwner($userId);
    }

    public static function companyId(): ?string
    {
        return self::company()['id'] ?? null;
    }

    public static function hasCompany(): bool
    {
        return self::companyId() !== null;
    }

    /** Reset cached resolution (used in tests / after creating a company mid-request). */
    public static function reset(): void
    {
        self::$companyCache = null;
        self::$resolved = false;
    }
}
