<?php
namespace App\Core;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Wrapper centralizado sobre mPDF.
 * Renderiza HTML y dispara la descarga (o stream inline) del PDF.
 *
 * mPDF tiene mejor soporte CSS que dompdf (gradients, mejor flex, mejor tipografía,
 * headers/footers nativos vía @page).
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
}
