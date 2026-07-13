<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Authentication & role helpers. The logged-in user is stored in the session.
 */
class Auth
{
    public const ROLES = ['super_admin', 'campus_admin', 'event_user', 'campus_staff'];

    public const ROLE_LABELS = [
        'super_admin'  => 'Super Admin',
        'campus_admin' => 'Campus Admin',
        'event_user'   => 'Event User',
        'campus_staff' => 'Campus Staff',
    ];

    public static function login(array $user): void
    {
        Session::regenerate();
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'email'     => $user['email'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
            'campus_id' => $user['campus_id'] !== null ? (int) $user['campus_id'] : null,
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        Session::destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    /** Campus scope of the current user (null for super admin = all campuses). */
    public static function campusId(): ?int
    {
        return $_SESSION['user']['campus_id'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    /** Human-readable label for the current (or given) role. */
    public static function roleLabel(?string $role = null): string
    {
        $role = $role ?? self::role();
        return self::ROLE_LABELS[$role] ?? ucfirst((string) $role);
    }
}
