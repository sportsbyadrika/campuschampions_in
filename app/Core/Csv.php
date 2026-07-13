<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSV export helper. Streams a UTF-8 (BOM) CSV download to the browser.
 */
class Csv
{
    /**
     * @param string   $filename  Download filename (without extension).
     * @param array    $headers   Column header labels.
     * @param iterable $rows      Rows as arrays of scalar values (order matches $headers).
     */
    public static function download(string $filename, array $headers, iterable $rows): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'cell'], $row), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    private static function cell(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        return (string) ($value ?? '');
    }
}
