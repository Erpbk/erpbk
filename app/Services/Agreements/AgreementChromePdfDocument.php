<?php

namespace App\Services\Agreements;

/**
 * Thin wrapper so controllers can call stream()/download()/output()/save()
 * the same way they do with Dompdf / AgreementMpdfDocument.
 */
class AgreementChromePdfDocument
{
    public function __construct(
        protected string $pdfBytes
    ) {}

    public function output(): string
    {
        return $this->pdfBytes;
    }

    public function stream(string $filename)
    {
        $filename = $this->safeFilename($filename);

        return response($this->pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(string $filename)
    {
        $filename = $this->safeFilename($filename);

        return response($this->pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function save(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($path, $this->pdfBytes);
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
