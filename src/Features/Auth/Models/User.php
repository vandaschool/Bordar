<?php

declare(strict_types=1);

namespace App\Features\Auth\Models;

use App\Core\Model;

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return static::findBy('email', mb_strtolower($email));
    }

    public static function emailExists(string $email): bool
    {
        return static::findByEmail($email) !== null;
    }
}
