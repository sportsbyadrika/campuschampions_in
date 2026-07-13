<?php
/**
 * Campus Champions - Front Controller
 * All web requests are routed through this file.
 */

declare(strict_types=1);

// Composer autoloader (vendored dependencies + PSR-4 App\ namespace)
require dirname(__DIR__) . '/vendor/autoload.php';

// Application configuration & constants
require dirname(__DIR__) . '/config/config.php';

use App\Core\App;

// Error reporting based on environment
if (config('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');

// Boot the application
$app = new App();
$app->run();
