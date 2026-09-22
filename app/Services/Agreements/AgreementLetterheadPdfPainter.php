<?php

namespace App\Services\Agreements;

use App\Models\AgreementCategory;
use Dompdf\Dompdf;
use Mpdf\Mpdf;

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
     * Company logo + contact HTML for mPDF SetHTMLHeader (default letterhead).
     * A body `position:fixed` overlay consumes page 1 and pushes content to page 2.
     */
    public function defaultHtmlHeader(?AgreementCategory $category): ?string
    {
        $mode = $category?->letterheadMode() ?? 'default';
        if ($mode !== 'default') {
            return null;
        }

        $branding = $this->pdfBranding->withUploadedLetterhead(
            $this->pdfBranding->forCompany($category?->company_id),
            $category
        );
        $logo = $this->pdfBranding->preparedMpdfLogo($category?->company_id);
        if ($logo !== null) {
            $branding['logo_src'] = $logo['src'];
            $branding['logo_width_mm'] = $logo['width_mm'];
            $branding['logo_height_mm'] = $logo['height_mm'];
            $branding['logo_width_px'] = $logo['width_px'];
            $branding['logo_height_px'] = $logo['height_px'];
        }

        return view('agreements.pdf.partials.mpdf-html-header', [
            'branding' => $branding,
            'headerTopMarginMm' => (float) config('agreement_letterhead.header_top_margin_mm', 8),
        ])->render();
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
