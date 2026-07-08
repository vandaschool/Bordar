<?php

declare(strict_types=1);

namespace App\Features\Auth\Models;

use App\Core\Model;

final class Role extends Model
{
    protected static string $table = 'roles';

    protected static bool $softDeletes = false;

    public static function findByName(string $name): ?array
    {
        return static::findBy('name', $name);
    }
}
