<?php
namespace App\Core;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Wrapper centralizado de generación de PDF.
 *  · ::stream()    → mPDF (mejor para reportes con headers/footers complejos)
 *  · ::streamDom() → dompdf (más fiel a CSS estándar para layouts simples y limpios)
 */
class Pdf
{
    /**
     * Genera un PDF a partir de HTML y lo envía al browser.
     *
     * @param string $html        HTML completo (con <html>, <head>, <body>).
     * @param string $filename    Nombre del archivo (sin path).
     * @param string $orientation 'portrait' | 'landscape' (P/L para mPDF).
     * @param string $paper       'A4' | 'Letter' | etc.
     * @param bool   $attachment  true = forzar descarga, false = mostrar inline.
     */
    public static function stream(string $html, string $filename, string $orientation = 'portrait', string $paper = 'A4', bool $attachment = true): void
    {
        // Subir memoria temporalmente: mPDF puede necesitar bastante con tablas grandes
        @ini_set('memory_limit', '512M');
        @set_time_limit(60);

        $tempDir = sys_get_temp_dir() . '/mpdf';
        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

        $orientation = strtolower($orientation);
        $orient = ($orientation === 'landscape' || $orientation === 'l') ? 'L' : 'P';

        try {
            $mpdf = new Mpdf([
                'mode'         => 'utf-8',
                'format'       => $paper . '-' . $orient,
                'tempDir'      => $tempDir,
                'default_font' => 'dejavusans',
                'margin_left'   => 12,
                'margin_right'  => 12,
                'margin_top'    => 16,
                'margin_bottom' => 16,
                'margin_header' => 6,
                'margin_footer' => 6,
                'autoScriptToLang' => true,
                'autoLangToFont'   => true,
            ]);
            $mpdf->SetTitle(pathinfo($filename, PATHINFO_FILENAME));
            $mpdf->SetCreator('Kydesk');
            $mpdf->SetAuthor('Kydesk');
            $mpdf->showImageErrors = false;

            $mpdf->WriteHTML($html);

            // Limpiar buffers antes de mandar headers
            while (ob_get_level() > 0) ob_end_clean();

            $dest = $attachment ? Destination::DOWNLOAD : Destination::INLINE;
            $mpdf->Output($filename, $dest);
            exit;
        } catch (\Throwable $e) {
            // Volver a buffer para que index.php pueda mostrar la pagina de error con detalle si debug=on
            throw new \RuntimeException('Error generando PDF: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Genera un PDF usando dompdf y lo envía al browser.
     * Mejor para layouts limpios estilo factura/cotización donde CSS estándar
     * basta y querés un render más fiel al HTML.
     */
    public static function streamDom(string $html, string $filename, string $orientation = 'portrait', string $paper = 'A4', bool $attachment = true): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(60);

        $options = new Options();
        $options->set('isRemoteEnabled', true);     // permite cargar el logo del tenant (URL absoluta o relativa)
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', BASE_PATH);

        try {
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper($paper, strtolower($orientation));
            $dompdf->render();

            while (ob_get_level() > 0) ob_end_clean();

            $dompdf->stream($filename, ['Attachment' => $attachment]);
            exit;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Error generando PDF (dompdf): ' . $e->getMessage(), 0, $e);
        }
    }
}
