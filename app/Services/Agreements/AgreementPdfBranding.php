<?php

namespace App\Services\Agreements;

use App\Services\Email\CompanyEmailBrandingService;

/**
 * Enriches company branding for agreement PDFs (Dompdf-safe logo + derived palette).
 */
class AgreementPdfBranding
{
    public function __construct(
        protected CompanyEmailBrandingService $companyBranding
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forCompany(?int $companyId): array
    {
        $branding = $this->companyBranding->resolve($companyId);

        $primary = $this->normalizeHex($branding['primary_color'] ?? '#1e3a8a');
        $secondary = $this->normalizeHex($branding['secondary_color'] ?? '#2563eb');

        $branding['primary_color'] = $primary;
        $branding['secondary_color'] = $secondary;
        $branding['primary_light'] = $this->mixWithWhite($primary, 0.92);
        $branding['primary_soft'] = $this->mixWithWhite($primary, 0.85);
        $branding['primary_muted'] = $this->mixWithWhite($primary, 0.72);
        $branding['primary_dark'] = $this->darken($primary, 0.22);
        $branding['secondary_light'] = $this->mixWithWhite($secondary, 0.9);
        $branding['text_on_primary'] = $this->contrastingTextColor($primary);
        $branding['border_color'] = $this->mixWithWhite($primary, 0.65);
        $branding['accent_line'] = $secondary;

        $logoPath = $this->companyBranding->resolveLogoFilesystemPath($companyId);
        $branding['logo_src'] = $this->logoToDataUri($logoPath);
        if ($branding['logo_src'] === null && !empty($branding['logo_url'])) {
            $branding['logo_src'] = $this->logoUrlToDataUri((string) $branding['logo_url']);
        }

        $branding['has_logo'] = $branding['logo_src'] !== null;
        $branding['location_line'] = trim(implode(' · ', array_filter([
            $branding['city'] ?? '',
            $branding['country'] ?? '',
        ])));

        return $branding;
    }

    public function logoToDataUri(?string $filesystemPath): ?string
    {
        if ($filesystemPath === null || !is_readable($filesystemPath)) {
            return null;
        }

        $mime = @mime_content_type($filesystemPath) ?: 'image/png';
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'], true)) {
            $mime = 'image/png';
        }

        $contents = @file_get_contents($filesystemPath);
        if ($contents === false || $contents === '') {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function logoUrlToDataUri(string $url): ?string
    {
        if (str_starts_with($url, 'data:')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            $path = public_path(ltrim(parse_url($url, PHP_URL_PATH) ?: '', '/'));
            if (is_readable($path)) {
                return $this->logoToDataUri($path);
            }
        }

        if (!str_starts_with($url, 'http')) {
            $url = url($url);
        }

        try {
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $contents = @file_get_contents($url, false, $context);
            if ($contents === false || $contents === '') {
                return null;
            }
            $mime = 'image/png';
            if (preg_match('/\.jpe?g$/i', $url)) {
                $mime = 'image/jpeg';
            }

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (\Throwable) {
            return null;
        }
    }

    public function normalizeHex(string $color): string
    {
        $color = trim($color);
        if ($color === '') {
            return '#1e3a8a';
        }
        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }
        if (strlen($color) === 4) {
            $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        return strtolower($color);
    }

    public function mixWithWhite(string $hex, float $whiteRatio): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $whiteRatio = max(0, min(1, $whiteRatio));

        return $this->rgbToHex(
            (int) round($r + (255 - $r) * $whiteRatio),
            (int) round($g + (255 - $g) * $whiteRatio),
            (int) round($b + (255 - $b) * $whiteRatio)
        );
    }

    public function darken(string $hex, float $amount): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $factor = 1 - max(0, min(1, $amount));

        return $this->rgbToHex(
            (int) round($r * $factor),
            (int) round($g * $factor),
            (int) round($b * $factor)
        );
    }

    public function contrastingTextColor(string $hex): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.55 ? '#1a1a1a' : '#ffffff';
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($this->normalizeHex($hex), '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }
}
