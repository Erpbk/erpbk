<?php

namespace App\Support;

use App\Services\Agreements\AgreementPdfBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicePdf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(string $view, array $data): DomPdfWrapper
    {
        $data['isPdf'] = true;

        if (empty($data['brand'])) {
            $data['brand'] = app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());
        }

        // Start at full size; shrink until the invoice fits on one A4 page.
        $scale = 1.0;
        $pdf = null;
        $minScale = 0.40;

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $data['pdfFitScale'] = $scale;

            $pdf = Pdf::loadView($view, $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96,
                ], true);

            $dompdf = $pdf->getDomPDF();
            $dompdf->render();
            $canvas = $dompdf->getCanvas();
            $pageCount = 1;
            if ($canvas && method_exists($canvas, 'get_page_count')) {
                $pageCount = max(1, (int) $canvas->get_page_count());
            }

            if ($pageCount <= 1 || $scale <= $minScale) {
                break;
            }

            // Shrink ~12% each pass (floor 0.40 so dense invoices still fit one page)
            $scale = max($minScale, round($scale * 0.88, 3));
        }

        return $pdf;
    }

    /**
     * Render an invoice Blade view to a downloadable A4 PDF with DomPDF-safe options.
     *
     * @param  array<string, mixed>  $data
     */
    public static function download(string $view, array $data, string $filename): Response|StreamedResponse
    {
        return self::make($view, $data)->download($filename);
    }
}
