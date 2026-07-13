<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Application kernel: boots services, loads routes and dispatches the request.
 */
class App
{
    private Router $router;

    public function __construct()
    {
        ErrorHandler::register();
        Session::start();

        $this->router = new Router();
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $router = $this->router;
        require BASE_PATH . '/config/routes.php';
    }

    public function run(): void
    {
        $method = Request::method();
        $uri    = Request::uri();

        $route = $this->router->match($method, $uri);

        if ($route === null) {
            // Distinguish 405 from 404
            if ($this->router->uriExists($uri)) {
                ErrorHandler::abort(405, 'Method not allowed.');
            }
            ErrorHandler::abort(404);
            return;
        }

        // Run middleware
        foreach ($route['middleware'] as $middleware) {
            Middleware::run($middleware);
        }

        // CSRF protection on all state-changing requests
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            Csrf::check();
        }

        $this->dispatch($route['handler'], $route['params']);
    }

    private function dispatch(array|string $handler, array $params): void
    {
        if (is_string($handler)) {
            // "ControllerName@method"
            [$class, $action] = explode('@', $handler);
        } else {
            [$class, $action] = $handler;
        }

        $fqcn = str_starts_with($class, 'App\\') ? $class : "App\\Controllers\\{$class}";

        if (!class_exists($fqcn)) {
            ErrorHandler::abort(500, "Controller {$fqcn} not found.");
        }

        $controller = new $fqcn();

        if (!method_exists($controller, $action)) {
            ErrorHandler::abort(500, "Action {$action} not found in {$fqcn}.");
        }

        call_user_func_array([$controller, $action], array_values($params));
    }
}
