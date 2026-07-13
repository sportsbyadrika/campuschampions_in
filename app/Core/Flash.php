<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One-request flash messages. Types: success, error, warning, info.
 */
class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function success(string $m): void { self::set('success', $m); }
    public static function error(string $m): void   { self::set('error', $m); }
    public static function warning(string $m): void { self::set('warning', $m); }
    public static function info(string $m): void    { self::set('info', $m); }

    /** Return and clear all flash messages. */
    public static function pull(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    public static function hasAny(): bool
    {
        return !empty($_SESSION['_flash']);
    }
}
