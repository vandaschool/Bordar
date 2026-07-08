<?php

declare(strict_types=1);

namespace App\Config;

final class Constants
{
    public const ROLE_APPLICANT = 'Applicant';
    public const ROLE_MENTOR = 'Mentor';
    public const ROLE_REVIEWER = 'Reviewer';
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_VENDOR = 'Vendor';
    public const ROLE_INVESTOR = 'Investor';

    public const DEFAULT_ROLES = [
        self::ROLE_APPLICANT,
        self::ROLE_MENTOR,
        self::ROLE_REVIEWER,
        self::ROLE_ADMIN,
        self::ROLE_VENDOR,
        self::ROLE_INVESTOR,
    ];
}
