<?php

namespace App\Services\Agreements;

use App\Models\AgreementCategory;
use Dompdf\Dompdf;

/**
 * Paints a full-page letterhead on every PDF page before content renders.
 */
class AgreementLetterheadPdfPainter
{
    public function __construct(
        protected AgreementPdfBranding $pdfBranding
    ) {}

    public function registerDompdfCallbacks(Dompdf $dompdf, ?AgreementCategory $category): void
    {
        if ($category === null || $category->letterheadMode() === 'none') {
            return;
        }

        $src = $this->resolveImageSource($category);
        if ($src === null) {
            return;
        }

        $dompdf->setCallbacks([
            [
                'event' => 'begin_page_render',
                'f' => function ($frame, $canvas, $fontMetrics) use ($src): void {
                    $canvas->image(
                        $src,
                        0,
                        0,
                        $canvas->get_width(),
                        $canvas->get_height()
                    );
                },
            ],
        ]);
    }

    private function resolveImageSource(AgreementCategory $category): ?string
    {
        $path = $category->letterheadFilesystemPath();
        if ($path !== null) {
            $real = realpath($path);
            if ($real !== false && is_readable($real)) {
                return str_replace('\\', '/', $real);
            }
        }

        return $this->pdfBranding->letterheadDataUri($category);
    }
}
