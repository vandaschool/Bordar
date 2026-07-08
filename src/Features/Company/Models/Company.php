<?php

declare(strict_types=1);

namespace App\Features\Company\Models;

use App\Core\Model;

final class Company extends Model
{
    protected static string $table = 'companies';

    public static function findByOwner(string $userId): ?array
    {
        return static::findBy('owner_user_id', $userId);
    }
}
