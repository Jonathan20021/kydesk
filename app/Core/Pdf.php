<?php
namespace App\Core;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Wrapper centralizado sobre dompdf.
 * Renderiza HTML y dispara la descarga (o stream inline) del PDF.
 */
class Pdf
{
    /**
     * Genera un PDF a partir de HTML y lo envía al browser.
     *
     * @param string $html        HTML completo (con <html>, <head>, <body>).
     * @param string $filename    Nombre del archivo (sin path).
     * @param string $orientation 'portrait' | 'landscape'.
     * @param string $paper       'A4' | 'letter' | etc.
     * @param bool   $attachment  true = forzar descarga, false = mostrar inline.
     */
    public static function stream(string $html, string $filename, string $orientation = 'portrait', string $paper = 'A4', bool $attachment = true): void
    {
        $opts = new Options();
        $opts->set('isRemoteEnabled', true);   // permitir cargar fuentes/imágenes externas si las hubiera
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('defaultFont', 'DejaVu Sans');
        $opts->set('chroot', [BASE_PATH]);     // restringir acceso a filesystem al directorio del proyecto
        $opts->set('tempDir', sys_get_temp_dir());

        $dompdf = new Dompdf($opts);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        // Limpiar cualquier output buffer previo
        while (ob_get_level() > 0) ob_end_clean();

        $dompdf->stream($filename, ['Attachment' => $attachment]);
        exit;
    }
}
