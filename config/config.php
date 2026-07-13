<?php
/**
 * Application configuration bootstrap.
 * Loads the .env file (simple parser) and exposes config via env()/config().
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Path constants
// ---------------------------------------------------------------------------
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/assets/uploads');

// ---------------------------------------------------------------------------
// Minimal .env loader (no external dependency)
// ---------------------------------------------------------------------------
if (!function_exists('load_env')) {
    function load_env(string $file): void
    {
        if (!is_readable($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            // Strip inline comments introduced by ` ;` or ` #`
            $value = preg_replace('/\s+[;#].*$/', '', $value);
            $value = trim((string) $value);
            // Strip surrounding quotes
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'")) {
                $value = substr($value, 1, -1);
            }
            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            'empty' => '',
            default => $value,
        };
    }
}

load_env(BASE_PATH . '/.env');

// ---------------------------------------------------------------------------
// Config array
// ---------------------------------------------------------------------------
$config = [
    'app' => [
        'name'    => env('APP_NAME', 'Campus Champions'),
        'env'     => env('APP_ENV', 'production'),
        'debug'   => (bool) env('APP_DEBUG', false),
        'url'     => rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    ],
    'db' => [
        'host'    => env('DB_HOST', 'localhost'),
        'port'    => env('DB_PORT', '3306'),
        'name'    => env('DB_NAME', 'campuschampions'),
        'user'    => env('DB_USER', 'root'),
        'pass'    => env('DB_PASS', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
    'session' => [
        'name'     => env('SESSION_NAME', 'cc_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 1800),
    ],
    'mail' => [
        'host'       => env('MAIL_HOST', ''),
        'port'       => (int) env('MAIL_PORT', 587),
        'username'   => env('MAIL_USERNAME', ''),
        'password'   => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_addr'  => env('MAIL_FROM_ADDRESS', 'no-reply@campuschampions.local'),
        'from_name'  => env('MAIL_FROM_NAME', 'Campus Champions'),
    ],
    'security' => [
        'login_max_attempts'   => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'login_lockout_minutes'=> (int) env('LOGIN_LOCKOUT_MINUTES', 15),
        'reset_token_minutes'  => (int) env('RESET_TOKEN_MINUTES', 60),
    ],
];

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        global $config;
        if ($key === null) {
            return $config;
        }
        $segments = explode('.', $key);
        $value = $config;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }
}

return $config;
