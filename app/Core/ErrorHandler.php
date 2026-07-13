<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Central error / exception handling with friendly error pages.
 */
class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }
        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        error_log(sprintf(
            "[%s] %s in %s:%d\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        self::render(500, $e);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log("Fatal: {$error['message']} in {$error['file']}:{$error['line']}");
            if (!headers_sent()) {
                self::render(500, null);
            }
        }
    }

    public static function abort(int $code, string $message = ''): never
    {
        self::render($code, null, $message);
        exit;
    }

    private static function render(int $code, ?Throwable $e = null, string $message = ''): void
    {
        if (!headers_sent()) {
            http_response_code($code);
        }

        // AJAX / JSON response
        if (Request::isAjax()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'message' => $message ?: self::defaultMessage($code),
            ]);
            return;
        }

        $view = APP_PATH . "/views/errors/{$code}.php";
        if (!is_file($view)) {
            $view = APP_PATH . '/views/errors/500.php';
        }

        $debug = config('app.debug') && $e !== null;
        $exception = $e;
        $customMessage = $message;

        if (is_file($view)) {
            include $view;
        } else {
            echo "<h1>Error {$code}</h1><p>" . htmlspecialchars($message ?: self::defaultMessage($code)) . '</p>';
        }
    }

    private static function defaultMessage(int $code): string
    {
        return match ($code) {
            403 => 'Access denied.',
            404 => 'The page you are looking for could not be found.',
            419 => 'Your session has expired. Please refresh and try again.',
            500 => 'Something went wrong on our end.',
            default => 'An error occurred.',
        };
    }
}
