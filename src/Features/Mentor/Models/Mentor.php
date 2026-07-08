<?php

declare(strict_types=1);

namespace App\Features\Mentor\Models;

use App\Core\Model;

final class Mentor extends Model
{
    protected static string $table = 'mentors';

    public static function findByUserId(string $userId): ?array
    {
        return static::findBy('user_id', $userId);
    }

    /** @return array<int, array<string, mixed>> */
    public static function allActive(): array
    {
        return static::all();
    }
}
