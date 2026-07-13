<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Route middleware runner. Supported directives:
 *   - "auth"                 : require an authenticated user
 *   - "guest"                : require NO authenticated user
 *   - "role:a,b,c"           : require the user to hold one of the roles
 */
class Middleware
{
    public static function run(string $directive): void
    {
        [$name, $param] = array_pad(explode(':', $directive, 2), 2, null);

        switch ($name) {
            case 'auth':
                if (!Auth::check()) {
                    Flash::warning('Please log in to continue.');
                    self::redirect('/login');
                }
                break;

            case 'guest':
                if (Auth::check()) {
                    self::redirect('/dashboard');
                }
                break;

            case 'role':
                if (!Auth::check()) {
                    Flash::warning('Please log in to continue.');
                    self::redirect('/login');
                }
                $roles = array_map('trim', explode(',', (string) $param));
                if (!Auth::is(...$roles)) {
                    ErrorHandler::abort(403, 'You do not have permission to access this resource.');
                }
                break;
        }
    }

    private static function redirect(string $path): never
    {
        $url = config('app.url') . '/' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }
}
