<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;

/**
 * Global view/helper functions.
 */

if (!function_exists('e')) {
    /** HTML-escape output (XSS prevention). */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** Absolute URL for an app path. */
    function url(string $path = ''): string
    {
        return config('app.url') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return config('app.url') . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('old')) {
    /** Retrieve previously submitted input flashed to the session. */
    function old(string $key, mixed $default = ''): string
    {
        $old = $_SESSION['_old'] ?? [];
        return e($old[$key] ?? $default);
    }
}

if (!function_exists('flash_old')) {
    /** Flash current input so a redirected form can repopulate. */
    function flash_old(array $data): void
    {
        unset($data['password'], $data['password_confirmation'], $data['csrf_token']);
        $_SESSION['_old'] = $data;
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        unset($_SESSION['_old']);
    }
}

if (!function_exists('error_for')) {
    /** Retrieve a validation error message for a field. */
    function error_for(string $key): ?string
    {
        return $_SESSION['_errors'][$key] ?? null;
    }
}

if (!function_exists('flash_errors')) {
    function flash_errors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }
}

if (!function_exists('clear_errors')) {
    function clear_errors(): void
    {
        unset($_SESSION['_errors']);
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('active_if')) {
    /** Return 'active-class' when the current URI starts with $prefix. */
    function active_if(string $prefix, string $class = 'nav-active'): string
    {
        $uri = Request::uri();
        return str_starts_with($uri, $prefix) ? $class : '';
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $value, string $format = 'Y-m-d'): string
    {
        if (empty($value) || $value === '0000-00-00' || str_starts_with((string) $value, '0000')) {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date($format, $ts) : '';
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $value): string
    {
        return format_date($value, 'Y-m-d H:i:s');
    }
}

if (!function_exists('status_badge')) {
    /** Render a coloured status pill. */
    function status_badge(string $status): string
    {
        $map = [
            'active'     => 'bg-emerald-100 text-emerald-700',
            'inactive'   => 'bg-slate-100 text-slate-600',
            'expired'    => 'bg-rose-100 text-rose-700',
            'trial'      => 'bg-amber-100 text-amber-700',
            'registered' => 'bg-blue-100 text-blue-700',
            'confirmed'  => 'bg-emerald-100 text-emerald-700',
            'cancelled'  => 'bg-rose-100 text-rose-700',
        ];
        $cls = $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600';
        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $cls . '">'
            . e(ucfirst($status)) . '</span>';
    }
}

if (!function_exists('can')) {
    /** Simple ability check based on role. */
    function can(string ...$roles): bool
    {
        return Auth::check() && Auth::is(...$roles);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        $url = str_starts_with($path, 'http') ? $path : url($path);
        header('Location: ' . $url);
        exit;
    }
}
