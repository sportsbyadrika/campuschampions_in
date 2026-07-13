<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renders PHP view templates within a layout.
 */
class View
{
    /**
     * Render a view file (app/views/<name>.php) inside an optional layout.
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $content = self::capture($view, $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        // Layout receives $content plus the shared data
        $data['content'] = $content;
        echo self::capture($layout, $data);
    }

    /** Render a view and return its output as a string (no layout). */
    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }

    private static function capture(string $view, array $data): string
    {
        $file = APP_PATH . '/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
