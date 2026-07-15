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
     * Compose the full certificate overlay from a template's layout config and
     * the placeholder data: a centred content box within the configured margins,
     * plus the certificate number (top-left) and date (bottom-left) placed at
     * their configured positions. All measurements are in millimetres.
     */
    public static function compose(array $tpl, array $data): string
    {
        $orientation = ($tpl['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait';
        [$pageW, $pageH] = $orientation === 'landscape' ? [297, 210] : [210, 297];

        $mt = (int) ($tpl['margin_top'] ?? 20);
        $mr = (int) ($tpl['margin_right'] ?? 20);
        $mb = (int) ($tpl['margin_bottom'] ?? 20);
        $ml = (int) ($tpl['margin_left'] ?? 20);
        $cw = max(10, $pageW - $ml - $mr);
        $ch = max(10, $pageH - $mt - $mb);

        $num  = htmlspecialchars((string) ($data['certificate_number'] ?? ''), ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars((string) ($data['issue_date'] ?? ''), ENT_QUOTES, 'UTF-8');
        $nt = (int) ($tpl['number_top'] ?? 12); $nl = (int) ($tpl['number_left'] ?? 15);
        $dt = (int) ($tpl['date_top'] ?? 262);  $dl = (int) ($tpl['date_left'] ?? 20);
        $nfs = (int) ($tpl['number_font_size'] ?? 11); $nfc = self::color($tpl['number_font_color'] ?? '#333333');
        $dfs = (int) ($tpl['date_font_size'] ?? 11);   $dfc = self::color($tpl['date_font_color'] ?? '#333333');

        $body = self::render((string) ($tpl['body_html'] ?? ''), $data);

        $out = '';
        if ($num !== '') {
            $out .= '<div style="position:absolute; top:' . $nt . 'mm; left:' . $nl . 'mm; font-family:\'DejaVu Sans\',sans-serif; font-size:' . $nfs . 'px; color:' . $nfc . ';">' . $num . '</div>';
        }
        if ($date !== '') {
            $out .= '<div style="position:absolute; top:' . $dt . 'mm; left:' . $dl . 'mm; font-family:\'DejaVu Sans\',sans-serif; font-size:' . $dfs . 'px; color:' . $dfc . ';">' . $date . '</div>';
        }
        $out .= '<div style="position:absolute; top:' . $mt . 'mm; left:' . $ml . 'mm; width:' . $cw . 'mm; height:' . $ch . 'mm; display:table;">'
              . '<div style="display:table-cell; vertical-align:middle; text-align:center;">' . $body . '</div></div>';
        return $out;
    }

    /** Validate a hex colour (#RGB or #RRGGBB) for safe inline-style use. */
    private static function color(mixed $value): string
    {
        $v = trim((string) $value);
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v) ? $v : '#333333';
    }

    /** Build a configured Dompdf instance for a certificate overlay. */
    private static function dompdf(string $html, string $orientation): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);   // no external fetches (security)
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page{margin:0}body{margin:0}</style></head><body>' . $html . '</body></html>';
        $dompdf->loadHtml($wrapped, 'UTF-8');
        $dompdf->setPaper('A4', $orientation === 'landscape' ? 'landscape' : 'portrait');
        $dompdf->render();
        return $dompdf;
    }

    /**
     * Generate a PDF from overlay HTML and save it. Returns web-relative path.
     */
    public static function generate(string $html, string $filename, string $orientation = 'portrait'): string
    {
        $dompdf = self::dompdf($html, $orientation);

        $dir = UPLOAD_PATH . '/certificates';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Certificate directory is not writable.');
        }
        $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) . '.pdf';
        file_put_contents($dir . '/' . $safe, $dompdf->output());

        return 'uploads/certificates/' . $safe;
    }

    /** Stream a certificate overlay PDF inline (used for template preview). */
    public static function stream(string $html, string $filename, string $orientation = 'portrait'): void
    {
        $dompdf = self::dompdf($html, $orientation);
        $dompdf->stream($filename . '.pdf', ['Attachment' => false]);
    }
}
