<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with view rendering, redirects and JSON helpers.
 */
abstract class Controller
{
    /** Render a view within the main layout. */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        $base = config('app.url');
        $url  = str_starts_with($path, 'http') ? $path : $base . '/' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    protected function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
        header('Location: ' . $ref);
        exit;
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Validate CSRF token on state-changing requests. */
    protected function verifyCsrf(): void
    {
        Csrf::check();
    }

    /** Abort with an error page. */
    protected function abort(int $code, string $message = ''): never
    {
        ErrorHandler::abort($code, $message);
    }

    /** Ensure current user has one of the given roles, else 403. */
    protected function authorize(string ...$roles): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        if (!empty($roles) && !Auth::is(...$roles)) {
            $this->abort(403, 'You do not have permission to access this resource.');
        }
    }

    /** Current page number from query, min 1. */
    protected function page(): int
    {
        return max(1, (int) Request::get('page', 1));
    }

    /** Per-page size, constrained to allowed values. */
    protected function perPage(int $default = 20): int
    {
        $allowed = [10, 20, 25, 50, 100];
        $pp = (int) Request::get('per_page', $default);
        return in_array($pp, $allowed, true) ? $pp : $default;
    }
}
