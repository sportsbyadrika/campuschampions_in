<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple route collection with named parameters, e.g. /courses/{id}/edit.
 * Supports optional per-route middleware (auth, role:super_admin, etc.).
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array|string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|string $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array|string $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /** Register the same handler for GET and POST. */
    public function any(string $path, array|string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array|string $handler, array $middleware): void
    {
        $path = '/' . trim($path, '/');
        $this->routes[$method][] = [
            'pattern'    => $this->compile($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $path): string
    {
        // Convert {param} to named regex groups
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    /**
     * Match the current request. Returns [handler, params, middleware] or null.
     */
    public function match(string $method, string $uri): ?array
    {
        $candidates = $this->routes[$method] ?? [];
        foreach ($candidates as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [
                    'handler'    => $route['handler'],
                    'params'     => $params,
                    'middleware' => $route['middleware'],
                ];
            }
        }
        return null;
    }

    /** Whether a URI matches any method (for 405 vs 404 distinction). */
    public function uriExists(string $uri): bool
    {
        foreach ($this->routes as $routes) {
            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $uri)) {
                    return true;
                }
            }
        }
        return false;
    }
}
