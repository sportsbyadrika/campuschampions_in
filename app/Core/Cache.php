<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal file-based cache (used for the public results page).
 */
class Cache
{
    private static function dir(): string
    {
        $dir = STORAGE_PATH . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function path(string $key): string
    {
        return self::dir() . '/' . sha1($key) . '.cache';
    }

    /** Get a cached value or null if missing/expired. */
    public static function get(string $key): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = @unserialize($raw);
        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            return null;
        }
        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            @unlink($file);
            return null;
        }
        return $data['value'];
    }

    /** Store a value with a TTL in seconds (0 = never expires). */
    public static function put(string $key, mixed $value, int $ttl = 60): void
    {
        $file = self::path($key);
        $payload = serialize([
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value'   => $value,
        ]);
        @file_put_contents($file, $payload, LOCK_EX);
    }

    /** Remember helper: return cached value or compute + store it. */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }
        $value = $callback();
        self::put($key, $value, $ttl);
        return $value;
    }

    /** Clear the entire cache (e.g. after result changes). */
    public static function flush(): void
    {
        foreach (glob(self::dir() . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }
}
