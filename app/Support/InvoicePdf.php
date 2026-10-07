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

        return Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 96,
            ], true);
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
