<?php

declare(strict_types=1);

namespace App\Features\LMS\Models;

use App\Core\Model;

final class Course extends Model
{
    protected static string $table = 'courses';

    /** @return array<int, array<string, mixed>> */
    public static function forCohort(string $cohortId): array
    {
        return static::where('cohort_id', $cohortId);
    }
}
