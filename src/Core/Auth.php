<?php

declare(strict_types=1);

namespace App\Core;

use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;

final class Auth
{
    private static ?array $userCache = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findBy('email', $email);

        if ($user === null || $user['is_active'] == 0) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::login($user['id']);

        return true;
    }

    public static function login(string $userId): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        self::$userCache = null;
    }

    public static function logout(): void
    {
        Session::remove('user_id');
        self::$userCache = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has('user_id') && self::user() !== null;
    }

    public static function id(): ?string
    {
        return Session::get('user_id');
    }

    public static function user(): ?array
    {
        if (self::$userCache !== null) {
            return self::$userCache;
        }

        $userId = self::id();

        if ($userId === null) {
            return null;
        }

        $user = User::find($userId);

        if ($user === null || (int) $user['is_active'] === 0) {
            return null;
        }

        return self::$userCache = $user;
    }

    public static function role(): ?array
    {
        $user = self::user();

        if ($user === null) {
            return null;
        }

        return Role::find($user['role_id']);
    }

    public static function can(string $permission): bool
    {
        $role = self::role();

        if ($role === null) {
            return false;
        }

        $permissions = json_decode($role['permissions'] ?? '[]', true) ?: [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public static function hasRole(string ...$roleNames): bool
    {
        $role = self::role();

        return $role !== null && in_array($role['name'], $roleNames, true);
    }
}
