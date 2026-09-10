<?php

namespace App\Services\Agreements;

use App\Models\AgreementCategory;
use Dompdf\Dompdf;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;

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

    /**
     * Full-page letterhead behind content for the mPDF agreement path.
     */
    public function applyToMpdf(Mpdf $mpdf, ?AgreementCategory $category): void
    {
        if ($category === null || $category->letterheadMode() === 'none') {
            return;
        }

        $src = $this->resolveFilesystemImage($category);
        if ($src === null) {
            return;
        }

        // Stretch to page size, top-left origin, fully opaque, behind HTML.
        $mpdf->SetWatermarkImage($src, 1, [$mpdf->w, $mpdf->h], [0, 0]);
        $mpdf->showWatermarkImage = true;
        $mpdf->watermarkImgBehind = true;
    }

    /**
     * Stamp full-page letterhead under each page of a Chrome-generated PDF.
     * Mirrors applyToMpdf (SetWatermarkImage) using mPDF+FPDI already in vendor.
     */
    public function applyToChromePdf(string $pdfBytes, ?AgreementCategory $category): string
    {
        if ($pdfBytes === '' || $category === null || $category->letterheadMode() === 'none') {
            return $pdfBytes;
        }

        $src = $this->resolveFilesystemImage($category);
        if ($src === null) {
            return $pdfBytes;
        }

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $inFile = $tempDir.DIRECTORY_SEPARATOR.'chrome_lh_'.bin2hex(random_bytes(8)).'.pdf';
        file_put_contents($inFile, $pdfBytes);

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'tempDir' => $tempDir,
            ]);
            $mpdf->SetDisplayMode('fullpage');

            $pageCount = $mpdf->SetSourceFile($inFile);
            if ($pageCount < 1) {
                return $pdfBytes;
            }

            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $mpdf->ImportPage($i);
                $size = $mpdf->GetTemplateSize($tplId);
                $w = (float) ($size['width'] ?? 0);
                $h = (float) ($size['height'] ?? 0);
                if ($w <= 0 || $h <= 0) {
                    continue;
                }

                $mpdf->AddPageByArray([
                    'orientation' => $w > $h ? 'L' : 'P',
                    'sheet-size' => [$w, $h],
                    'margin-left' => 0,
                    'margin-right' => 0,
                    'margin-top' => 0,
                    'margin-bottom' => 0,
                ]);

                // Letterhead first (underlay), then Chrome content on top — same as watermarkImgBehind.
                $mpdf->Image($src, 0, 0, $w, $h, '', '', true, false, false, false);
                $mpdf->UseTemplate($tplId, 0, 0, $w, $h);
            }

            $out = $mpdf->Output('', Destination::STRING_RETURN);
            if (! is_string($out) || $out === '') {
                throw new RuntimeException('Failed to stamp letterhead onto Chrome PDF.');
            }

            return $out;
        } finally {
            if (is_file($inFile)) {
                @unlink($inFile);
            }
        }
    }

    private function resolveImageSource(AgreementCategory $category): ?string
    {
        $path = $this->resolveFilesystemImage($category);
        if ($path !== null) {
            return $path;
        }

        return $this->pdfBranding->letterheadDataUri($category);
    }

    private function resolveFilesystemImage(AgreementCategory $category): ?string
    {
        $path = $category->letterheadFilesystemPath();
        if ($path !== null) {
            $real = realpath($path);
            if ($real !== false && is_readable($real)) {
                return str_replace('\\', '/', $real);
            }
        }

        return null;
    }
}
