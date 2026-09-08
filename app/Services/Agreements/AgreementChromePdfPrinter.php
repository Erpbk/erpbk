<?php

namespace App\Services\Agreements;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Print agreement HTML to PDF via Chrome/Edge headless (logical Unicode Arabic).
 */
class AgreementChromePdfPrinter
{
    /** @var list<string> */
    private const WINDOWS_CANDIDATES = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ];

    /** @var list<string> */
    private const UNIX_CANDIDATES = [
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/microsoft-edge',
        '/usr/bin/microsoft-edge-stable',
    ];

    public static function detectBinary(): ?string
    {
        $localAppData = getenv('LOCALAPPDATA') ?: (getenv('USERPROFILE') ? getenv('USERPROFILE').'\\AppData\\Local' : '');
        $extra = [];
        if ($localAppData !== '') {
            $extra[] = $localAppData.'\\Google\\Chrome\\Application\\chrome.exe';
            $extra[] = $localAppData.'\\Microsoft\\Edge\\Application\\msedge.exe';
        }

        foreach (array_merge(self::WINDOWS_CANDIDATES, $extra, self::UNIX_CANDIDATES) as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public function isAvailable(): bool
    {
        return self::detectBinary() !== null;
    }

    /**
     * Write HTML to a temp file, print with Chrome/Edge headless, return PDF bytes.
     * If $outputPath is set, also writes the PDF there.
     */
    public function htmlToPdf(string $html, ?string $outputPath = null): string
    {
        $binary = self::detectBinary();
        if ($binary === null) {
            throw new RuntimeException('Chrome/Edge binary not found for agreement PDF printing.');
        }

        $base = storage_path('app');
        if (! is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        $profileDir = $base.DIRECTORY_SEPARATOR.'chrome-pdf-profile';
        if (! is_dir($profileDir)) {
            @mkdir($profileDir, 0775, true);
        }

        $id = bin2hex(random_bytes(8));
        $htmlPath = $base.DIRECTORY_SEPARATOR.'chrome-pdf-'.$id.'.html';
        $pdfPath = $outputPath ?: ($base.DIRECTORY_SEPARATOR.'chrome-pdf-'.$id.'.pdf');
        $pdfDir = dirname($pdfPath);
        if (! is_dir($pdfDir)) {
            @mkdir($pdfDir, 0775, true);
        }

        file_put_contents($htmlPath, $html);

        try {
            $htmlUri = $this->fileUri($htmlPath);
            $process = new Process([
                $binary,
                '--headless=new',
                '--disable-gpu',
                '--allow-file-access-from-files',
                '--user-data-dir='.$profileDir,
                '--no-pdf-header-footer',
                '--print-to-pdf='.$pdfPath,
                $htmlUri,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) < 64) {
                $err = trim($process->getErrorOutput().' '.$process->getOutput());
                throw new RuntimeException(
                    'Chrome headless print-to-PDF failed'.($err !== '' ? ': '.$err : '.')
                );
            }

            $bytes = file_get_contents($pdfPath);
            if ($bytes === false || $bytes === '') {
                throw new RuntimeException('Chrome produced an empty PDF.');
            }

            return $bytes;
        } finally {
            if (is_file($htmlPath)) {
                @unlink($htmlPath);
            }
            if ($outputPath === null && is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }

    /**
     * Print HTML and keep the PDF at $outputPath. Returns absolute path.
     */
    public function htmlToPdfFile(string $html, string $outputPath): string
    {
        $this->htmlToPdf($html, $outputPath);

        return $outputPath;
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (! str_starts_with($normalized, '/')) {
            // Windows drive path → /C:/...
            $normalized = '/'.$normalized;
        }

        return 'file://'.$normalized;
    }
}
