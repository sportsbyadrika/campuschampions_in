<?php

declare(strict_types=1);

namespace App\Core;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renders certificate HTML (with {{placeholders}}) into a stored PDF.
 */
class CertificatePdf
{
    /**
     * Replace {{placeholders}} in the template body with values (escaped).
     */
    public static function render(string $templateHtml, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', function ($m) use ($data) {
            $key = strtolower($m[1]);
            return isset($data[$key]) ? htmlspecialchars((string) $data[$key], ENT_QUOTES, 'UTF-8') : '';
        }, $templateHtml);
    }

    /**
     * Generate a PDF from HTML and save it. Returns web-relative path.
     */
    public static function generate(string $html, string $filename): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);   // no external fetches (security)
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page{margin:0}body{margin:0}</style></head><body>' . $html . '</body></html>';
        $dompdf->loadHtml($wrapped, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $dir = UPLOAD_PATH . '/certificates';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Certificate directory is not writable.');
        }
        $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) . '.pdf';
        file_put_contents($dir . '/' . $safe, $dompdf->output());

        return 'uploads/certificates/' . $safe;
    }
}
