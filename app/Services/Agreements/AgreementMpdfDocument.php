<?php

namespace App\Services\Agreements;

use Illuminate\Http\Response;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Thin wrapper so controllers can call stream()/download()/output()
 * the same way they do with barryvdh/laravel-dompdf.
 */
class AgreementMpdfDocument
{
    public function __construct(
        protected Mpdf $mpdf
    ) {}

    public function getMpdf(): Mpdf
    {
        return $this->mpdf;
    }

    public function output(): string
    {
        return $this->mpdf->Output('', Destination::STRING_RETURN);
    }

    public function stream(string $filename)
    {
        $filename = $this->safeFilename($filename);

        return response($this->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(string $filename)
    {
        $filename = $this->safeFilename($filename);

        return response($this->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function save(string $path): void
    {
        $this->mpdf->Output($path, Destination::FILE);
    }

    private function safeFilename(string $filename): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            $filename = 'agreement.pdf';
        }
        if (! str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return str_replace(['"', "\r", "\n"], '', $filename);
    }
}
