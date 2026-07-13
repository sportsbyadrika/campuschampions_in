<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token generation & verification (synchronizer token pattern).
 */
class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /** Hidden input for forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        $stored = $_SESSION[self::KEY] ?? '';
        return is_string($token) && $stored !== '' && hash_equals($stored, $token);
    }

    /**
     * Validate the token from the current request (form field or header).
     * Aborts with 419 on failure.
     */
    public static function check(): void
    {
        $token = $_POST['csrf_token']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        if (!self::verify($token)) {
            http_response_code(419);
            if (Request::isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh and try again.']);
            } else {
                echo 'Invalid or expired security token (CSRF). Please go back and try again.';
            }
            exit;
        }
    }
}
