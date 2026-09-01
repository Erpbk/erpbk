<?php

namespace App\Services\Agreements;

use App\Models\AgreementCategory;
use GdImage;

class AgreementLetterheadLayout
{
    /**
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function resolvedMarginsMm(?AgreementCategory $category): array
    {
        $defaults = $this->defaultMarginsMm();

        $saved = $category?->letterhead_margins;
        $side = config('agreement_letterhead.side_margins_mm', ['left' => 12, 'right' => 12]);

        return [
            'top' => $this->clamp(
                (float) (is_array($saved) ? ($saved['top'] ?? $defaults['top']) : $defaults['top']),
                30,
                100
            ),
            'bottom' => $this->clamp(
                (float) (is_array($saved) ? ($saved['bottom'] ?? $defaults['bottom']) : $defaults['bottom']),
                0,
                50
            ),
            'left' => $this->clamp(
                (float) (is_array($saved) ? ($saved['left'] ?? $side['left']) : $side['left']),
                5,
                55
            ),
            'right' => $this->clamp(
                (float) (is_array($saved) ? ($saved['right'] ?? $side['right']) : $side['right']),
                5,
                55
            ),
        ];
    }

    /**
     * Estimate safe content margins from a full-page letterhead image.
     *
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function suggestMarginsFromFilesystem(?string $filesystemPath): array
    {
        if ($filesystemPath === null || ! is_readable($filesystemPath)) {
            return $this->defaultMarginsMm();
        }

        return $this->detectMarginsFromImage($filesystemPath);
    }

    /**
     * Scan letterhead artwork to find header, footer, and side decoration bounds.
     *
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function detectMarginsFromImage(string $filesystemPath): array
    {
        $defaults = $this->defaultMarginsMm();
        $image = $this->loadImage($filesystemPath);

        if (! $image instanceof GdImage) {
            return $this->fallbackFractionMargins();
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 10 || $height < 10) {
            imagedestroy($image);

            return $this->fallbackFractionMargins();
        }

        $zones = config('agreement_letterhead.auto_zones', []);
        $inkThreshold = (int) ($zones['ink_threshold'] ?? 235);
        $rowInkMin = (float) ($zones['row_ink_ratio'] ?? 0.012);
        $colInkMin = (float) ($zones['col_ink_ratio'] ?? 0.04);
        $buffer = (float) ($zones['buffer_mm'] ?? 8);
        $sampleStep = max(1, (int) round($width / 420));

        $headerBottomPx = 0;
        $headerScanEnd = (int) floor($height * 0.48);
        for ($y = 0; $y < $headerScanEnd; $y++) {
            if ($this->rowInkRatio($image, $y, $width, $sampleStep, $inkThreshold) >= $rowInkMin) {
                $headerBottomPx = $y;
            }
        }

        $footerTopPx = $height;
        $footerScanStart = (int) floor($height * 0.52);
        for ($y = $height - 1; $y >= $footerScanStart; $y--) {
            if ($this->rowInkRatio($image, $y, $width, $sampleStep, $inkThreshold) >= $rowInkMin) {
                $footerTopPx = min($footerTopPx, $y);
            }
        }

        $contentTopPx = min($height, $headerBottomPx + (int) round($height * 0.02));
        $contentBottomPx = max(0, $footerTopPx - (int) round($height * 0.02));

        $leftInkPx = 0;
        $leftScanEnd = (int) floor($width * 0.45);
        for ($x = 0; $x < $leftScanEnd; $x++) {
            if ($this->columnInkRatio($image, $x, $contentTopPx, $contentBottomPx, $sampleStep, $inkThreshold) >= $colInkMin) {
                $leftInkPx = $x;
            }
        }

        $rightInkPx = $width;
        $rightScanStart = (int) floor($width * 0.55);
        for ($x = $width - 1; $x >= $rightScanStart; $x--) {
            if ($this->columnInkRatio($image, $x, $contentTopPx, $contentBottomPx, $sampleStep, $inkThreshold) >= $colInkMin) {
                $rightInkPx = min($rightInkPx, $x);
            }
        }

        imagedestroy($image);

        $pageW = $this->pageWidthMm();
        $pageH = $this->pageHeightMm();

        $top = $this->pxToMm($headerBottomPx, $height, $pageH) + $buffer;
        $bottom = $this->pxToMm($height - $footerTopPx, $height, $pageH) + $buffer;
        $left = $this->pxToMm($leftInkPx, $width, $pageW) + $buffer;
        $right = $this->pxToMm($width - $rightInkPx, $width, $pageW) + $buffer;

        return [
            'top' => $this->clamp(round($top, 1), 30, 100),
            'bottom' => $this->clamp(round($bottom, 1), 35, 115),
            'left' => $this->clamp(round($left, 1), 10, 50),
            'right' => $this->clamp(round($right, 1), 10, 50),
        ];
    }

    /**
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function defaultMarginsMm(): array
    {
        $pageH = $this->pageHeightMm();
        $headerReserve = (float) config('agreement_letterhead.header_reserve_mm', 32);
        $topGapPct = (float) config('agreement_letterhead.content_top_gap_pct', 0.02);
        $bottomPct = (float) config('agreement_letterhead.content_bottom_pct', 0.034);
        $side = config('agreement_letterhead.side_margins_mm', ['left' => 12, 'right' => 12]);
        $footer = (float) config('agreement_letterhead.footer_reserve_mm', 10);

        return [
            'top' => round($headerReserve + ($pageH * $topGapPct), 1),
            'bottom' => $footer > 0 ? $footer : round($pageH * $bottomPct, 1),
            'left' => (float) ($side['left'] ?? 12),
            'right' => (float) ($side['right'] ?? 12),
        ];
    }

    /**
     * Content margins for agreements printed without letterhead chrome.
     * Uses the same vertical safe zone as letterhead mode so content aligns on pre-printed paper.
     *
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function plainDocumentMarginsMm(?AgreementCategory $category): array
    {
        $padding = $this->contentPaddingMm($category, false);
        $resolved = $this->resolvedMarginsMm($category);

        return [
            'top' => $padding['top'],
            'bottom' => $padding['bottom'],
            'left' => $resolved['left'],
            'right' => $resolved['right'],
        ];
    }

    /**
     * CSS padding for the content flow area (mm).
     * Letterhead chrome is an overlay, so top padding stays the full page margin
     * whether or not the digital header is drawn (pre-printed paper aligns).
     *
     * @return array{top: float, bottom: float}
     */
    public function contentPaddingMm(?AgreementCategory $category, bool $withLetterhead): array
    {
        $m = $this->resolvedMarginsMm($category);

        return [
            'top' => $m['top'],
            'bottom' => $m['bottom'],
        ];
    }

    public function pageMarginCss(?AgreementCategory $category): string
    {
        $m = $this->resolvedMarginsMm($category);

        return sprintf(
            '%smm %smm %smm %smm',
            $m['top'],
            $m['right'],
            $m['bottom'],
            $m['left']
        );
    }

    public function contentHeightMm(?AgreementCategory $category): float
    {
        $m = $this->resolvedMarginsMm($category);

        return max(40, $this->pageHeightMm($category) - $m['top'] - $m['bottom']);
    }

    /**
     * Usable content height per page, aligned with letterhead.blade.php layout.
     * Identical with or without digital letterhead so pre-printed paper aligns.
     * Same budget for preview and PDF download so page breaks match.
     */
    public function contentZoneHeightMm(?AgreementCategory $category = null, bool $withLetterhead = true): float
    {
        $m = $this->resolvedMarginsMm($category);

        return max(40, $this->pageHeightMm($category) - $m['top'] - $m['bottom']);
    }

    /**
     * @return array{key: string, label: string, width_mm: float, height_mm: float, dompdf: string}
     */
    public function resolvedPageSize(?AgreementCategory $category = null): array
    {
        $saved = $category?->letterhead_margins;
        $key = is_array($saved) ? (string) ($saved['page_size'] ?? '') : '';

        return $this->pageSizeByKey($key);
    }

    /**
     * @return array{key: string, label: string, width_mm: float, height_mm: float, dompdf: string}
     */
    public function pageSizeByKey(?string $key): array
    {
        $sizes = $this->pageSizeCatalog();
        $default = (string) config('agreement_letterhead.default_page_size', 'a4');
        $key = strtolower(trim((string) $key));

        if ($key === '' || ! isset($sizes[$key])) {
            $key = isset($sizes[$default]) ? $default : 'a4';
        }

        $size = $sizes[$key];

        return [
            'key' => $key,
            'label' => (string) $size['label'],
            'width_mm' => (float) $size['width_mm'],
            'height_mm' => (float) $size['height_mm'],
            'dompdf' => (string) ($size['dompdf'] ?? $key),
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedPageSizeKeys(): array
    {
        return array_keys($this->pageSizeCatalog());
    }

    /**
     * @return array<string, array{label: string, width_mm: float, height_mm: float, dompdf: string}>
     */
    public function pageSizeCatalog(): array
    {
        $sizes = config('agreement_letterhead.page_sizes', []);

        return is_array($sizes) && $sizes !== [] ? $sizes : [
            'a4' => ['label' => 'A4', 'width_mm' => 210.0, 'height_mm' => 297.0, 'dompdf' => 'a4'],
        ];
    }

    public function pageWidthMm(?AgreementCategory $category = null): float
    {
        return $this->resolvedPageSize($category)['width_mm'];
    }

    public function pageHeightMm(?AgreementCategory $category = null): float
    {
        return $this->resolvedPageSize($category)['height_mm'];
    }

    private function fallbackFractionMargins(): array
    {
        $zones = config('agreement_letterhead.auto_zones', []);
        $headerFraction = (float) ($zones['header_fraction'] ?? 0.22);
        $footerFraction = (float) ($zones['footer_fraction'] ?? 0.22);
        $buffer = (float) ($zones['buffer_mm'] ?? 8);
        $pageH = $this->pageHeightMm();
        $defaults = $this->defaultMarginsMm();

        return [
            'top' => $this->clamp(round($pageH * $headerFraction + $buffer, 1), 30, 100),
            'bottom' => $this->clamp(round($pageH * $footerFraction + $buffer, 1), 35, 115),
            'left' => $defaults['left'],
            'right' => $defaults['right'],
        ];
    }

    private function loadImage(string $path): ?GdImage
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        return $image instanceof GdImage ? $image : null;
    }

    private function rowInkRatio(GdImage $image, int $y, int $width, int $sampleStep, int $inkThreshold): float
    {
        $ink = 0;
        $samples = 0;

        for ($x = 0; $x < $width; $x += $sampleStep) {
            $samples++;
            if ($this->isInkPixel(imagecolorat($image, $x, $y), $inkThreshold)) {
                $ink++;
            }
        }

        return $samples > 0 ? $ink / $samples : 0.0;
    }

    private function columnInkRatio(
        GdImage $image,
        int $x,
        int $topPx,
        int $bottomPx,
        int $sampleStep,
        int $inkThreshold
    ): float {
        if ($bottomPx <= $topPx) {
            return 0.0;
        }

        $ink = 0;
        $samples = 0;

        for ($y = $topPx; $y < $bottomPx; $y += $sampleStep) {
            $samples++;
            if ($this->isInkPixel(imagecolorat($image, $x, $y), $inkThreshold)) {
                $ink++;
            }
        }

        return $samples > 0 ? $ink / $samples : 0.0;
    }

    private function isInkPixel(int $rgb, int $threshold): bool
    {
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return $r < $threshold || $g < $threshold || $b < $threshold;
    }

    private function pxToMm(int $px, int $totalPx, float $pageMm): float
    {
        if ($totalPx <= 0) {
            return 0.0;
        }

        return ($px / $totalPx) * $pageMm;
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
