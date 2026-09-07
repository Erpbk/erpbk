<?php

namespace App\Services\Agreements;

use App\Models\AgreementCategory;
use App\Services\Email\CompanyEmailBrandingService;
use App\Support\PublicStorageDisk;

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
        $branding['website_display'] = $this->resolveWebsiteDisplay($branding);

        return $branding;
    }

    /**
     * @param  array<string, mixed>  $branding
     * @return array<string, mixed>
     */
    public function withUploadedLetterhead(array $branding, ?AgreementCategory $category, bool $preferPublicUrl = false): array
    {
        if ($category) {
            $category->loadMissing(['letterhead', 'watermark']);
        }

        $mode = $category?->letterheadMode() ?? 'default';
        $src = null;
        if ($mode === 'library') {
            $src = $preferPublicUrl
                ? ($this->letterheadPublicUrl($category) ?: $this->letterheadDataUri($category))
                : $this->letterheadDataUri($category);
        }

        $branding['letterhead_mode'] = $mode;
        $branding['letterhead_src'] = $src;
        $branding['has_uploaded_letterhead'] = $src !== null;
        $branding['watermark_mode'] = $category?->watermarkMode() ?? 'none';
        $branding['watermark_src'] = $this->resolvedWatermarkSrc($branding, $category);

        return $branding;
    }

    /**
     * @param  array<string, mixed>  $branding
     */
    public function resolvedWatermarkSrc(array $branding, ?AgreementCategory $category): ?string
    {
        $mode = $category?->watermarkMode() ?? 'none';
        if ($mode === 'library') {
            return $this->watermarkDataUri($category);
        }
        if ($mode === 'default') {
            return $branding['logo_src'] ?? null;
        }

        return null;
    }

    public function letterheadPublicUrl(?AgreementCategory $category): ?string
    {
        if (! $category || ! $category->hasLetterhead()) {
            return null;
        }

        $path = $category->letterheadFilesystemPath();
        if ($path === null) {
            return null;
        }

        $mime = @mime_content_type($path) ?: '';
        if (str_contains($mime, 'pdf')) {
            return null;
        }

        $relative = $category->letterheadRelativePath();
        if ($relative === null || $relative === '') {
            return null;
        }

        return PublicStorageDisk::url($relative);
    }

    /**
     * Data URI for an agreement category letterhead image (Dompdf-safe).
     */
    public function letterheadDataUri(?AgreementCategory $category): ?string
    {
        if (! $category) {
            return null;
        }

        $path = $category->letterheadFilesystemPath();
        if ($path === null) {
            return null;
        }

        $mime = @mime_content_type($path) ?: '';
        if (str_contains($mime, 'pdf')) {
            return null;
        }

        return $this->logoToDataUri($path);
    }

    public function watermarkDataUri(?AgreementCategory $category): ?string
    {
        if (! $category) {
            return null;
        }

        $watermark = $category->watermark;
        $path = $watermark?->filesystemPath();
        if ($path === null) {
            return null;
        }

        $mime = @mime_content_type($path) ?: '';
        if (str_contains($mime, 'pdf')) {
            return null;
        }

        return $this->logoToDataUri($path);
    }

    /**
     * Rewrite img src attributes in agreement HTML to Dompdf-safe data URIs.
     */
    public function inlineHtmlImages(string $html): string
    {
        if ($html === '' || ! str_contains(strtolower($html), '<img')) {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-image-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('agreement-image-root');
        if (! $root instanceof \DOMElement) {
            return $html;
        }

        foreach ($root->getElementsByTagName('img') as $img) {
            if (! $img instanceof \DOMElement) {
                continue;
            }

            $this->prepareContentImage($img);
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out !== '' ? $out : $html;
    }

    public function imageSrcToDataUri(string $src): ?string
    {
        $src = html_entity_decode(trim($src), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($src === '' || str_starts_with($src, 'data:') || str_starts_with($src, 'blob:')) {
            return str_starts_with($src, 'data:') ? $src : null;
        }

        $storagePath = $this->publicStorageRelativePath($src);
        if ($storagePath !== null) {
            $path = PublicStorageDisk::readablePath($storagePath);

            return $this->logoToDataUri($path);
        }

        return $this->logoUrlToDataUri($src);
    }

    /**
     * TinyMCE convert_urls turns /storage/foo into ../../../../../storage/foo
     * relative to the editor URL. Dompdf cannot resolve that.
     */
    private function publicStorageRelativePath(string $src): ?string
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $src;
        }

        $path = str_replace('\\', '/', $path);
        if (! preg_match('~(?:^|/)storage/([^?#]+)~i', $path, $match)) {
            return null;
        }

        return ltrim($match[1], '/');
    }

    /**
     * Dompdf cannot size height:auto and cannot paint WebP. Give each img a
     * PNG/JPEG data URI plus explicit pixel width/height that fit the page.
     */
    private function prepareContentImage(\DOMElement $img): void
    {
        $src = trim($img->getAttribute('src'));
        $dataUri = $this->imageSrcToDataUri($src);
        if ($dataUri === null) {
            return;
        }

        $dataUri = $this->ensureDompdfSafeDataUri($dataUri);
        [$widthPx, $heightPx] = $this->contentImageDisplaySizePx($img, $dataUri);
        $dataUri = $this->resizeDataUriTo($dataUri, $widthPx, $heightPx);
        $img->setAttribute('src', $dataUri);
        $img->setAttribute('width', (string) $widthPx);
        $img->setAttribute('height', (string) $heightPx);

        $style = preg_replace(
            '/(?:^|;)\s*(?:width|height|max-width)\s*:\s*[^;]+/i',
            '',
            $img->getAttribute('style')
        ) ?? '';
        $style = trim($style, "; \t\n\r");
        $img->setAttribute(
            'style',
            trim($style.';width:'.$widthPx.'px;height:'.$heightPx.'px;', '; ')
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function contentImageDisplaySizePx(\DOMElement $img, string $dataUri): array
    {
        $maxWidth = 700;
        $maxHeight = 880;
        $width = 400;
        $height = 300;

        $bytes = $this->dataUriBytes($dataUri);
        if ($bytes !== null) {
            $info = @getimagesizefromstring($bytes);
            if (is_array($info) && ($info[0] ?? 0) > 0 && ($info[1] ?? 0) > 0) {
                $width = (int) $info[0];
                $height = (int) $info[1];
            }
        }

        $attrW = (int) $img->getAttribute('width');
        $attrH = (int) $img->getAttribute('height');
        if ($attrW > 0 && $attrH > 0) {
            $width = $attrW;
            $height = $attrH;
        }

        $scale = min(1.0, $maxWidth / max(1, $width), $maxHeight / max(1, $height));

        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }

    private function resizeDataUriTo(string $dataUri, int $widthPx, int $heightPx): string
    {
        $widthPx = max(1, $widthPx);
        $heightPx = max(1, $heightPx);
        $bytes = $this->dataUriBytes($dataUri);
        if ($bytes === null || ! function_exists('imagecreatefromstring')) {
            return $dataUri;
        }

        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return $dataUri;
        }

        if (imagesx($source) === $widthPx && imagesy($source) === $heightPx) {
            imagedestroy($source);

            return $dataUri;
        }

        $dest = imagecreatetruecolor($widthPx, $heightPx);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $clear = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefilledrectangle($dest, 0, 0, $widthPx, $heightPx, $clear);
        imagecopyresampled(
            $dest,
            $source,
            0,
            0,
            0,
            0,
            $widthPx,
            $heightPx,
            imagesx($source),
            imagesy($source)
        );
        imagedestroy($source);

        ob_start();
        imagepng($dest);
        imagedestroy($dest);
        $png = ob_get_clean();
        if (! is_string($png) || $png === '') {
            return $dataUri;
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function ensureDompdfSafeDataUri(string $dataUri): string
    {
        $bytes = $this->dataUriBytes($dataUri);
        if ($bytes === null) {
            return $dataUri;
        }

        $mime = 'image/png';
        if (preg_match('#^data:(image/[a-zA-Z0-9.+-]+)#', $dataUri, $match)) {
            $mime = strtolower($match[1]);
        }

        return $this->bytesToDompdfDataUri($bytes, $mime) ?? $dataUri;
    }

    private function dataUriBytes(string $dataUri): ?string
    {
        if (! preg_match('#^data:image/[a-zA-Z0-9.+-]+;base64,(.+)$#s', $dataUri, $match)) {
            return null;
        }

        $bytes = base64_decode($match[1], true);

        return $bytes === false || $bytes === '' ? null : $bytes;
    }

    private function bytesToDompdfDataUri(string $contents, string $mime): ?string
    {
        $mime = strtolower($mime);
        if (in_array($mime, ['image/webp', 'image/x-webp'], true) && function_exists('imagecreatefromstring')) {
            $gd = @imagecreatefromstring($contents);
            if ($gd !== false) {
                ob_start();
                imagepng($gd);
                imagedestroy($gd);
                $png = ob_get_clean();
                if (is_string($png) && $png !== '') {
                    $contents = $png;
                    $mime = 'image/png';
                }
            }
        }

        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'], true)) {
            $mime = 'image/png';
        }

        if ($contents === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
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

        return $this->bytesToDompdfDataUri($contents, $mime);
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

    /**
     * @param  array<string, mixed>  $branding
     */
    private function resolveWebsiteDisplay(array $branding): string
    {
        $appUrl = trim((string) ($branding['app_url'] ?? ''));
        if ($appUrl === '') {
            return '';
        }

        $display = preg_replace('#^https?://#i', '', $appUrl) ?? $appUrl;
        $display = rtrim($display, '/');

        return $display;
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
