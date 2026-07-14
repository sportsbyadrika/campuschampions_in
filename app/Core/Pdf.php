<?php

declare(strict_types=1);

namespace App\Core;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generic HTML -> PDF streaming (Dompdf). Used for printable report lists with
 * repeating headers and "Page X of Y" footers.
 */
class Pdf
{
    /**
     * Stream a PDF inline to the browser (opens in a new tab).
     *
     * @param string $html        Full HTML document (may include a
     *                            <script type="text/php"> block for page numbers).
     * @param string $filename    Download name (without extension).
     * @param string $orientation 'portrait' | 'landscape'
     */
    public static function stream(string $html, string $filename, string $orientation = 'portrait'): never
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true); // enables the page-number script
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation === 'landscape' ? 'landscape' : 'portrait');
        $dompdf->render();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) . '.pdf';
        $dompdf->stream($safe, ['Attachment' => 0]); // inline
        exit;
    }

    /** The page-number footer script (centred "Page X of Y"). */
    public static function pageNumberScript(): string
    {
        return <<<'PHP'
        <script type="text/php">
        if (isset($pdf)) {
            $w = $pdf->get_width();
            $h = $pdf->get_height();
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 8;
            $tw = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text(($w - $tw) / 2, $h - 28, $text, $font, $size, array(0.35, 0.35, 0.35));
        }
        </script>
        PHP;
    }
}
