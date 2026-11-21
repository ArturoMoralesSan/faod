<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

/**
 * Convierte HTML en PDF con dompdf, con el número de página en el pie.
 */
class PdfRenderer
{
    public static function render(string $html, string $filename, bool $landscape = false): Response
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', $landscape ? 'landscape' : 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('DejaVu Sans');
        $canvas->page_text(
            $canvas->get_width() - 28 - $metrics->getTextWidth('Página 00 de 00', $font, 7),
            $canvas->get_height() - 20,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $font,
            7,
            [0.45, 0.47, 0.51],
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
