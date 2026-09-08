<?php

namespace App\Services\Agreements;

use App\Models\AgreementTemplate;
use App\Models\Riders;
use App\Services\Agreements\AgreementLetterheadLayout;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Email\CompanyEmailBrandingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class AgreementPdfService
{
    public function __construct(
        protected AgreementPlaceholderResolver $resolver,
        protected CompanyEmailBrandingService $branding,
        protected AgreementPdfBranding $pdfBranding,
        protected AgreementLetterheadLayout $letterheadLayout,
        protected AgreementLetterheadPaginator $letterheadPaginator,
        protected AgreementFontSettings $fonts,
        protected AgreementLetterheadPdfPainter $letterheadPainter,
        protected AgreementRtlTextShaper $rtlTextShaper,
        protected AgreementChromePdfPrinter $chromePdfPrinter
    ) {}

    /**
     * Stream the PDF in the browser (preview/print) or force a download.
     */
    public function httpResponse($pdf, string $filename, Request $request)
    {
        if ($request->boolean('inline') || ($request->exists('download') && ! $request->boolean('download'))) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    public function renderHtml(
        AgreementTemplate $template,
        Riders $rider,
        ?string $agreementDate = null,
        bool $useSampleData = false,
        bool $withLetterhead = true
    ): string {
        return $this->renderHtmlForModule($template, 'riders', $rider, $agreementDate, $useSampleData, false, $withLetterhead);
    }

    public function renderHtmlForModule(
        AgreementTemplate $template,
        string $module,
        Model $record,
        ?string $agreementDate = null,
        bool $useSampleData = false,
        bool $forPdf = false,
        bool $withLetterhead = true,
        ?string $pdfEngine = null
    ): string {
        $content = (string) ($template->description ?? '');
        $map = $useSampleData
            ? $this->sampleMap()
            : $this->resolver->resolveForModule($module, $record, $agreementDate);

        $body = $this->pdfBranding->inlineHtmlImages(
            $this->fonts->normalizeHtml($this->resolver->replace($content, $map))
        );

        $template->loadMissing(['category.letterhead', 'category.watermark']);
        $category = $template->category;

        // PDF engines:
        // - chrome: logical Unicode HTML (no Ar-PHP shaping); Chrome shapes Arabic.
        // - mpdf: logical Unicode + OpenType; mark Arabic blocks for RTL CSS.
        // Never run Dompdf-era utf8Glyphs / bdo / LRO on either path.
        $agreementHasArabic = false;
        if ($forPdf) {
            $pdfEngine = $pdfEngine ?: $this->resolvePdfEngine();
            $agreementHasArabic = $this->rtlTextShaper->containsArabicScript($body);
            if ($pdfEngine === 'mpdf') {
                $prepared = $this->rtlTextShaper->prepareLogicalHtmlForMpdf($body);
                $body = $prepared['html'];
                $agreementHasArabic = ! empty($prepared['has_arabic']);
            } elseif ($pdfEngine === 'chrome') {
                // Logical Unicode + <bdi dir="ltr"> around phones/IDs/chassis/plates.
                $prepared = $this->rtlTextShaper->prepareLogicalHtmlForChrome($body);
                $body = $prepared['html'];
                $agreementHasArabic = ! empty($prepared['has_arabic']);
            }
        } else {
            $pdfEngine = 'html';
        }
        $branding = $this->pdfBranding->withUploadedLetterhead(
            $this->pdfBranding->forCompany($template->company_id),
            $category
        );

        $subject = app(AgreementModuleService::class)->pdfSubject($module, $record);

        $contentZoneMm = $this->letterheadLayout->contentZoneHeightMm($category, $withLetterhead);
        $margins = $this->letterheadLayout->resolvedMarginsMm($category);
        $contentPadding = $this->letterheadLayout->contentPaddingMm($category, $withLetterhead);
        $pages = $this->letterheadPaginator->paginate($body, $contentZoneMm);
        $pdfFontFaces = $this->pdfFontFaces();

        return view('agreements.pdf.letterhead', [
            'body' => $body,
            'pages' => $pages,
            'contentZoneHeightMm' => $contentZoneMm,
            'branding' => $branding,
            'letterheadMargins' => $margins,
            'contentPadding' => $contentPadding,
            'pageMarginCss' => $this->letterheadLayout->pageMarginCss($category),
            'pageWidthMm' => $this->letterheadLayout->pageWidthMm($category),
            'pageHeightMm' => $this->letterheadLayout->pageHeightMm($category),
            'forPdf' => $forPdf,
            'pdfEngine' => $pdfEngine,
            'withLetterhead' => $withLetterhead,
            'agreementRtl' => false,
            'agreementHasArabic' => $agreementHasArabic,
            'rider' => $subject,
            'template' => $template,
            'category' => $category,
            'agreementDate' => $agreementDate ?? now()->format('Y-m-d'),
            'pdfFontFaces' => $pdfFontFaces,
            'agreementFontFamily' => ($forPdf && $pdfEngine === 'mpdf')
                ? $this->mpdfFamilyStackCss($this->fonts->defaultFamily(), $this->fonts->familyStackCss())
                : $this->fonts->familyStackCss(),
            'agreementRtlFontFamily' => ($forPdf && $pdfEngine === 'mpdf')
                ? $this->mpdfRtlFamilyStackCss()
                : $this->fonts->rtlFamilyStackCss(),
            'agreementFontSizePt' => $this->fonts->sizePt(),
            // mPDF Arabic metrics wrap a bit looser than Chrome/Noto; tighten slightly for page parity.
            'agreementLineHeight' => ($forPdf && $pdfEngine === 'mpdf')
                ? max(1.2, round($this->fonts->lineHeight() * 0.9, 2))
                : $this->fonts->lineHeight(),
            'agreementFontColor' => $this->fonts->color(),
            'agreementHeadingSizesPt' => $this->fonts->headingSizesPt(),
        ])->render();
    }

    public function generatePdf(
        AgreementTemplate $template,
        Riders $rider,
        ?string $agreementDate = null,
        bool $withLetterhead = true
    ) {
        return $this->generatePdfForModule($template, 'riders', $rider, $agreementDate, $withLetterhead);
    }

    public function generatePdfForModule(
        AgreementTemplate $template,
        string $module,
        Model $record,
        ?string $agreementDate = null,
        bool $withLetterhead = true
    ) {
        $template->loadMissing(['category.letterhead', 'category.watermark']);
        $engine = $this->resolvePdfEngine();
        $html = $this->renderHtmlForModule($template, $module, $record, $agreementDate, false, true, $withLetterhead, $engine);

        return $this->buildPdf($html, $template->category, $withLetterhead, $engine);
    }

    public function previewPdf(
        AgreementTemplate $template,
        ?Riders $rider = null,
        ?string $agreementDate = null,
        bool $withLetterhead = true
    ) {
        $rider = $rider ?? new Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']);
        $template->loadMissing(['category.letterhead', 'category.watermark']);
        $engine = $this->resolvePdfEngine();
        $html = $this->renderHtmlForModule(
            $template,
            'riders',
            $rider,
            $agreementDate,
            $rider->exists === false,
            true,
            $withLetterhead,
            $engine
        );

        return $this->buildPdf($html, $template->category, $withLetterhead, $engine);
    }

    /**
     * Prefer Chrome/Edge headless when available; fall back to mPDF (English-ok).
     */
    private function resolvePdfEngine(): string
    {
        return $this->chromePdfPrinter->isAvailable() ? 'chrome' : 'mpdf';
    }

    /**
     * Build agreement PDFs via Chrome headless or mPDF.
     */
    private function buildPdf(string $html, ?\App\Models\AgreementCategory $category = null, bool $withLetterhead = true, ?string $engine = null)
    {
        $engine = $engine ?: $this->resolvePdfEngine();
        if ($engine === 'chrome') {
            return $this->buildChromePdf($html);
        }

        return $this->buildMpdf($html, $category, $withLetterhead);
    }

    private function buildChromePdf(string $html): AgreementChromePdfDocument
    {
        $bytes = $this->chromePdfPrinter->htmlToPdf($html);

        return new AgreementChromePdfDocument($bytes);
    }

    /**
     * Build agreement PDFs with mPDF (logical Unicode + OpenType Arabic shaping).
     */
    private function buildMpdf(string $html, ?\App\Models\AgreementCategory $category = null, bool $withLetterhead = true)
    {
        $hasArabic = str_contains($html, 'agreement-ar')
            || str_contains($html, 'agreement-ar-block')
            || $this->rtlTextShaper->containsArabicScript($html);

        if ($hasArabic) {
            $html = $this->fonts->forceRtlFontFamiliesInHtml($html);
            // Prefer Noto Naskh Arabic (Chrome parity) when registered with useOTL; Lateef fallback.
            // GPOS Type 5 Format 3 is skipped via scripts/patch-mpdf-gpos.php so Noto/Amiri can load.
            $html = $this->forceLateefFontsForMpdfHtml($html);
        }

        try {
            return $this->createMpdfDocument($html, $category, $withLetterhead, true);
        } catch (\Throwable $e) {
            if (! $this->isMpdfGposFailure($e)) {
                throw $e;
            }
            // Live Amiri/Noto/Scheherazade (or other faces) can still trip unsupported GPOS;
            // rebuild once with all useOTL disabled so the request does not 500.
            return $this->createMpdfDocument($html, $category, $withLetterhead, false);
        }
    }

    private function createMpdfDocument(
        string $html,
        ?\App\Models\AgreementCategory $category,
        bool $withLetterhead,
        bool $enableOtl
    ): AgreementMpdfDocument {
        $size = $this->letterheadLayout->resolvedPageSize($category);
        $fontConfig = $this->mpdfFontConfig($enableOtl);

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $defaultFont = $this->mpdfFontKey($this->fonts->defaultFamily());
        if (! isset($fontConfig['fontdata'][$defaultFont])) {
            $defaultFont = $this->preferredMpdfArabicFontKey($fontConfig['fontdata'])
                ?? (isset($fontConfig['fontdata']['dejavusans']) ? 'dejavusans' : 'lateef');
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [$size['width_mm'], $size['height_mm']],
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir' => $tempDir,
            'fontDir' => $fontConfig['fontDir'],
            'fontdata' => $fontConfig['fontdata'],
            'default_font' => $defaultFont,
            'default_font_size' => $this->fonts->sizePt(),
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'useSubstitutions' => true,
            'curlAllowUnsafeSslRequests' => true,
        ]);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetTitle('Agreement');

        // Letterhead is embedded in HTML (same model as Chrome) for visual parity.
        // Do NOT also SetWatermarkImage via letterheadPainter — that double-draws / blows up logos.
        // Painter remains available for legacy callers but is unused on the live agreement path.

        // Large letterhead/logo data-URIs exceed default pcre.backtrack_limit in mPDF.
        $html = $this->materializeDataUrisForMpdf($html, $tempDir);
        $previousLimit = ini_get('pcre.backtrack_limit');
        @ini_set('pcre.backtrack_limit', (string) max(10000000, (int) $previousLimit));
        // Some Arabic TTFs trip undefined-offset notices inside mPDF GSUB/GPOS readers;
        // Laravel promotes those to ErrorException — ignore only those font metrics notices.
        $previousReporting = error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
        set_error_handler(static function () {
            return true;
        });
        try {
            $mpdf->WriteHTML($html);
            $this->suppressTrailingEmptyMpdfPage($mpdf);
        } finally {
            restore_error_handler();
            error_reporting($previousReporting);
            if ($previousLimit !== false) {
                @ini_set('pcre.backtrack_limit', (string) $previousLimit);
            }
        }

        return new AgreementMpdfDocument($mpdf);
    }

    private function isMpdfGposFailure(\Throwable $e): bool
    {
        if ($e instanceof \Mpdf\MpdfException) {
            return true;
        }
        $message = $e->getMessage();

        return stripos($message, 'GPOS') !== false
            || stripos($message, 'GSUB') !== false
            || stripos($message, 'ttfontsuni') !== false;
    }

    /**
     * @return array{fontDir: list<string>, fontdata: array<string, array<string, mixed>>}
     */
    private function mpdfFontConfig(bool $enableOtl = true): array
    {
        $defaults = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaults['fontDir'];
        $bundled = $this->fonts->bundledFontDirectory();
        if (is_dir($bundled)) {
            $fontDirs[] = $bundled;
        }
        $fontDirs[] = storage_path('fonts');

        $fontDefaults = (new FontVariables())->getDefaults();
        $fontData = $fontDefaults['fontdata'];

        $lateefFile = $bundled.'Lateef-Regular.ttf';
        if (! is_readable($lateefFile)) {
            throw new \RuntimeException(
                'Lateef-Regular.ttf is required as Arabic fallback for mPDF. Place it under resources/fonts/agreements/.'
            );
        }

        // Prefer Noto Naskh Arabic (matches Chrome @font-face). Amiri/Scheherazade also OK
        // after scripts/patch-mpdf-gpos.php skips unsupported GPOS Type 5 Format 3.
        $arabicFaces = [
            'notonaskharabic' => [
                'R' => 'NotoNaskhArabic-Regular.ttf',
                'B' => 'NotoNaskhArabic-Bold.ttf',
            ],
            'amiri' => [
                'R' => 'Amiri-Regular.ttf',
                'B' => 'Amiri-Bold.ttf',
                'I' => 'Amiri-Italic.ttf',
                'BI' => 'Amiri-BoldItalic.ttf',
            ],
            'scheherazadenew' => [
                'R' => 'ScheherazadeNew-Regular.ttf',
                'B' => 'ScheherazadeNew-Bold.ttf',
            ],
            'lateef' => [
                'R' => 'Lateef-Regular.ttf',
            ],
        ];

        foreach ($arabicFaces as $key => $files) {
            $regular = $bundled.($files['R'] ?? '');
            if ($regular === $bundled || ! is_readable($regular)) {
                continue;
            }
            $entry = ['R' => $files['R']];
            foreach (['B', 'I', 'BI'] as $slot) {
                if (! empty($files[$slot]) && is_readable($bundled.$files[$slot])) {
                    $entry[$slot] = $files[$slot];
                }
            }
            if ($enableOtl) {
                $entry['useOTL'] = 0xFF;
                $entry['useKashida'] = 75;
            }
            $fontData[$key] = $entry;
        }

        // Alias common CSS keys onto the same face files.
        if (isset($fontData['notonaskharabic'])) {
            $fontData['notonaskh'] = $fontData['notonaskharabic'];
        }
        if (isset($fontData['scheherazadenew'])) {
            $fontData['scheherazade'] = $fontData['scheherazadenew'];
        }

        if (! $enableOtl) {
            foreach ($fontData as $key => $meta) {
                if (is_array($meta) && array_key_exists('useOTL', $meta)) {
                    unset($fontData[$key]['useOTL'], $fontData[$key]['useKashida']);
                }
            }
        }

        // Map bundled Latin substitutes (Carlito≈Calibri, etc.) when present.
        $latinMap = [
            'calibri' => ['R' => 'Carlito-Regular.ttf', 'B' => 'Carlito-Bold.ttf', 'I' => 'Carlito-Italic.ttf', 'BI' => 'Carlito-BoldItalic.ttf'],
            'carlito' => ['R' => 'Carlito-Regular.ttf', 'B' => 'Carlito-Bold.ttf', 'I' => 'Carlito-Italic.ttf', 'BI' => 'Carlito-BoldItalic.ttf'],
            'arial' => ['R' => 'Carlito-Regular.ttf', 'B' => 'Carlito-Bold.ttf', 'I' => 'Carlito-Italic.ttf', 'BI' => 'Carlito-BoldItalic.ttf'],
            'timesnewroman' => ['R' => 'Tinos-Regular.ttf', 'B' => 'Tinos-Bold.ttf', 'I' => 'Tinos-Italic.ttf', 'BI' => 'Tinos-BoldItalic.ttf'],
            'tinos' => ['R' => 'Tinos-Regular.ttf', 'B' => 'Tinos-Bold.ttf', 'I' => 'Tinos-Italic.ttf', 'BI' => 'Tinos-BoldItalic.ttf'],
            'cambria' => ['R' => 'Caladea-Regular.ttf', 'B' => 'Caladea-Bold.ttf', 'I' => 'Caladea-Italic.ttf', 'BI' => 'Caladea-BoldItalic.ttf'],
            'caladea' => ['R' => 'Caladea-Regular.ttf', 'B' => 'Caladea-Bold.ttf', 'I' => 'Caladea-Italic.ttf', 'BI' => 'Caladea-BoldItalic.ttf'],
            'couriernew' => ['R' => 'Cousine-Regular.ttf', 'B' => 'Cousine-Bold.ttf', 'I' => 'Cousine-Italic.ttf', 'BI' => 'Cousine-BoldItalic.ttf'],
            'cousine' => ['R' => 'Cousine-Regular.ttf', 'B' => 'Cousine-Bold.ttf', 'I' => 'Cousine-Italic.ttf', 'BI' => 'Cousine-BoldItalic.ttf'],
            'dejavusans' => ['R' => 'DejaVuSans.ttf', 'B' => 'DejaVuSans-Bold.ttf', 'I' => 'DejaVuSans-Oblique.ttf', 'BI' => 'DejaVuSans-BoldOblique.ttf'],
        ];

        foreach ($latinMap as $key => $files) {
            $regular = $bundled.($files['R'] ?? '');
            if ($regular !== $bundled && is_readable($regular)) {
                $fontData[$key] = $files;
            }
        }

        $arabicOtlKeys = ['notonaskharabic', 'notonaskh', 'amiri', 'scheherazadenew', 'scheherazade', 'lateef'];

        // Also register any cached faces from AgreementFontSettings.
        foreach ($this->fonts->cachedFaces() as $face) {
            $family = (string) ($face['family'] ?? '');
            $path = (string) ($face['path'] ?? '');
            if ($family === '' || $path === '' || ! is_readable($path)) {
                continue;
            }
            $key = $this->mpdfFontKey($family);
            $weight = strtolower((string) ($face['weight'] ?? 'normal'));
            $style = strtolower((string) ($face['style'] ?? 'normal'));
            $slot = 'R';
            if ($weight === 'bold' && $style === 'italic') {
                $slot = 'BI';
            } elseif ($weight === 'bold') {
                $slot = 'B';
            } elseif ($style === 'italic') {
                $slot = 'I';
            }
            if (! isset($fontData[$key])) {
                $fontData[$key] = [];
            }
            $basename = basename($path);
            $dir = dirname($path);
            if (! in_array($dir, $fontDirs, true)) {
                $fontDirs[] = $dir;
            }
            $fontData[$key][$slot] = $basename;
            if (in_array($key, $arabicOtlKeys, true)) {
                if ($enableOtl) {
                    $fontData[$key]['useOTL'] = 0xFF;
                    $fontData[$key]['useKashida'] = 75;
                } else {
                    unset($fontData[$key]['useOTL'], $fontData[$key]['useKashida']);
                }
            } elseif (! $enableOtl) {
                unset($fontData[$key]['useOTL'], $fontData[$key]['useKashida']);
            }
        }

        return [
            'fontDir' => array_values(array_unique($fontDirs)),
            'fontdata' => $fontData,
        ];
    }

    /**
     * mPDF fontdata key for the Arabic face that best matches Chrome (Noto), else Lateef.
     *
     * @param  array<string, mixed>|null  $fontData
     */
    private function preferredMpdfArabicFontKey(?array $fontData = null): ?string
    {
        $bundled = $this->fonts->bundledFontDirectory();
        $candidates = [
            'notonaskharabic' => $bundled.'NotoNaskhArabic-Regular.ttf',
            'amiri' => $bundled.'Amiri-Regular.ttf',
            'scheherazadenew' => $bundled.'ScheherazadeNew-Regular.ttf',
            'lateef' => $bundled.'Lateef-Regular.ttf',
        ];
        foreach ($candidates as $key => $file) {
            if ($fontData !== null && ! isset($fontData[$key])) {
                continue;
            }
            if (is_readable($file)) {
                return $key;
            }
        }

        return isset($fontData['lateef']) ? 'lateef' : null;
    }

    private function mpdfFontKey(string $family): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $family) ?? $family);
    }

    private function mpdfRtlFamilyStackCss(): string
    {
        $key = $this->preferredMpdfArabicFontKey() ?? 'lateef';
        $parts = [$key];
        if ($key !== 'lateef') {
            $parts[] = 'lateef';
        }

        return implode(', ', $parts);
    }

    private function mpdfFamilyStackCss(string $defaultFamily, string $fallbackStack): string
    {
        $key = $this->mpdfFontKey($defaultFamily);
        $parts = [];
        if ($key !== '') {
            $parts[] = $key;
        }
        foreach (preg_split('/\s*,\s*/', $fallbackStack) ?: [] as $name) {
            $name = trim($name, " \t\n\r\0\x0B'\"");
            if ($name === '') {
                continue;
            }
            $k = $this->mpdfFontKey($name);
            if ($k !== '' && ! in_array($k, $parts, true)) {
                $parts[] = $k;
            }
        }
        $arabic = $this->preferredMpdfArabicFontKey() ?? 'lateef';
        if (! in_array($arabic, $parts, true)) {
            $parts[] = $arabic;
        }
        if ($arabic !== 'lateef' && ! in_array('lateef', $parts, true)) {
            $parts[] = 'lateef';
        }
        $parts[] = 'dejavusans';

        return implode(', ', $parts);
    }

    /**
     * Remap display font names to mPDF keys, then force Arabic faces onto Noto (or Lateef fallback).
     * Method name kept for BC with unit tests / call sites; target face is preferredMpdfArabicFontKey().
     * Requires scripts/patch-mpdf-gpos.php so Noto/Amiri load with useOTL.
     */
    public function forceLateefFontsForMpdfHtml(string $html): string
    {
        $html = $this->remapFontFamiliesForMpdf($html);
        $target = $this->preferredMpdfArabicFontKey() ?? 'lateef';

        $arabicPattern = 'lateef|amiri|notonaskharabic|scheherazadenew|scheherazade'
            .'|noto\s*naskh\s*arabic|noto\s*sans\s*arabic|noto\s*naskh|xb\s*zar|xbzar';

        $replaceFamily = static function (string $chunk) use ($arabicPattern, $target): string {
            return preg_replace_callback(
                '/font-family\s*:\s*[^;]+/i',
                static function (array $m) use ($arabicPattern, $target): string {
                    return preg_match('/'.$arabicPattern.'/i', $m[0])
                        ? 'font-family: '.$target
                        : $m[0];
                },
                $chunk
            ) ?? $chunk;
        };

        $html = preg_replace_callback(
            '/(style\s*=\s*)([\'"])(.*?)\2/is',
            static function (array $m) use ($replaceFamily): string {
                return $m[1].$m[2].$replaceFamily($m[3]).$m[2];
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '/(<style\b[^>]*>)(.*?)(<\/style>)/is',
            static function (array $m) use ($arabicPattern, $target): string {
                $css = preg_replace_callback(
                    '/font-family\s*:\s*[^;}\n]+/i',
                    static function (array $fm) use ($arabicPattern, $target): string {
                        return preg_match('/'.$arabicPattern.'/i', $fm[0])
                            ? 'font-family: '.$target
                            : $fm[0];
                    },
                    $m[2]
                ) ?? $m[2];

                return $m[1].$css.$m[3];
            },
            $html
        ) ?? $html;

        return $html;
    }

    private function remapFontFamiliesForMpdf(string $html): string
    {
        $arabicTarget = $this->preferredMpdfArabicFontKey() ?? 'lateef';
        $map = [
            'Lateef' => 'lateef',
            'Amiri' => $arabicTarget,
            'Noto Naskh Arabic' => $arabicTarget,
            'Noto Sans Arabic' => $arabicTarget,
            'Scheherazade New' => $arabicTarget,
            'Scheherazade' => $arabicTarget,
            'Calibri' => 'calibri',
            'Arial' => 'arial',
            'Times New Roman' => 'timesnewroman',
            'Cambria' => 'cambria',
            'Courier New' => 'couriernew',
            'DejaVu Sans' => 'dejavusans',
        ];

        foreach ($map as $from => $to) {
            $html = str_ireplace(
                ["font-family: '{$from}'", "font-family:\"{$from}\"", "font-family:{$from}", "font-family: {$from}"],
                "font-family: {$to}",
                $html
            );
        }

        return $html;
    }

    /**
     * Drop a trailing blank mPDF page when content barely overflowed (Chrome often stays on 1).
     */
    private function suppressTrailingEmptyMpdfPage(Mpdf $mpdf): void
    {
        if ($mpdf->page < 2) {
            return;
        }
        $last = $mpdf->pages[$mpdf->page] ?? '';
        // Fixed letterhead chrome still writes operators; treat as empty if almost no text showing ops.
        $textOps = preg_match_all('/\((?:\\\\.|[^\\\\)]){2,}\)\s*Tj/s', $last) ?: 0;
        $textOps += preg_match_all('/\[(?:[^\]]*)\]\s*TJ/s', $last) ?: 0;
        if ($textOps <= 2 && strlen($last) < 2500) {
            $mpdf->DeletePages($mpdf->page);
        }
    }
    /**
     * Absolute TTF path for the Arabic face used when measuring wrap width.
     */
    private function resolveArabicFontPath(): ?string
    {
        $preferred = $this->fonts->rtlDefaultFamily();
        foreach ($this->fonts->cachedFaces() as $face) {
            if (($face['family'] ?? '') === $preferred
                && ($face['weight'] ?? '') === 'normal'
                && ($face['style'] ?? '') === 'normal'
                && is_readable($face['path'] ?? '')
            ) {
                return (string) $face['path'];
            }
        }

        $bundled = $this->fonts->bundledFontDirectory().'Amiri-Regular.ttf';

        return is_readable($bundled) ? $bundled : null;
    }

    /**
     * Load the same TTF files the editor, HTML preview, and print use.
     *
     * @return list<array{family: string, weight: string, style: string, path: string, uri: string, url: string}>
     */
    private function pdfFontFaces(): array
    {
        $faces = [];
        foreach ($this->fonts->cachedFaces() as $face) {
            $faces[] = [
                'family' => $face['family'],
                'weight' => $face['weight'],
                'style' => $face['style'],
                'path' => $face['path'],
                'uri' => $this->fileUri($face['path']),
                'url' => $face['url'],
            ];
        }

        return $faces;
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (! str_starts_with($normalized, '/')) {
            $normalized = '/' . $normalized;
        }

        return 'file://' . $normalized;
    }


    /**
     * Replace bulky data:image URIs with temp files so mPDF/PCRE can parse the HTML.
     */
        private function materializeDataUrisForMpdf(string $html, string $tempDir): string
    {
        if (! str_contains($html, 'data:image')) {
            return $html;
        }

        return preg_replace_callback(
            '/src=(["\'])(data:image\/([a-z0-9+.-]+);base64,([A-Za-z0-9+\/=]+))\1/i',
            function (array $m) use ($tempDir): string {
                $quote = $m[1];
                $ext = strtolower($m[3]);
                $ext = match ($ext) {
                    'jpeg' => 'jpg',
                    'svg+xml' => 'svg',
                    default => preg_replace('/[^a-z0-9]/', '', $ext) ?: 'img',
                };
                $bin = base64_decode($m[4], true);
                if ($bin === false || $bin === '') {
                    return $m[0];
                }
                $file = rtrim($tempDir, '\\/').DIRECTORY_SEPARATOR.'img_'.sha1($m[4]).'.'.$ext;
                if (! is_file($file)) {
                    @file_put_contents($file, $bin);
                    $this->downscaleRasterForMpdf($file, $ext);
                }

                return 'src='.$quote.str_replace('\\', '/', $file).$quote;
            },
            $html
        ) ?? $html;
    }

    /**
     * Shrink oversized landscape/square rasters (company logos) so mPDF cannot
     * layout from multi-megapixel intrinsic dimensions when CSS max-* is ignored.
     * Portrait near-A4 letterhead designs are left alone.
     */
    private function downscaleRasterForMpdf(string $file, string $ext): void
    {
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || ! is_readable($file)) {
            return;
        }
        $info = @getimagesize($file);
        if ($info === false) {
            return;
        }
        [$w, $h] = $info;
        if ($w < 800) {
            return;
        }
        // Full-page letterheads are portrait (~0.7 w/h). Logos are usually wider than tall.
        if ($h > 0 && ($w / $h) < 0.95) {
            return;
        }
        $maxW = 480;
        $newW = $maxW;
        $newH = max(1, (int) round($h * ($maxW / $w)));
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG => @imagecreatefrompng($file),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file) : false,
            IMAGETYPE_GIF => @imagecreatefromgif($file),
            default => false,
        };
        if ($src === false) {
            return;
        }
        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);

            return;
        }
        if ($info[2] === IMAGETYPE_PNG || $info[2] === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($dst, $file, 82),
            IMAGETYPE_PNG => imagepng($dst, $file, 6),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($dst, $file, 82) : imagepng($dst, $file, 6),
            IMAGETYPE_GIF => imagegif($dst, $file),
            default => null,
        };
        imagedestroy($src);
        imagedestroy($dst);
    }

    private function sampleMap(): array
    {
        return [
            '{rider_name}' => 'Sample Rider Name',
            '{rider_code}' => 'R-0001',
            '{rider_email}' => 'rider@example.com',
            '{rider_phone}' => '0500000000',
            '{rider_cnic}' => '784-0000-0000000-0',
            '{rider_passport_number}' => 'AB1234567',
            '{rider_nationality}' => 'Pakistan',
            '{rider_date_of_birth}' => '01-Jan-1990',
            '{rider_gender}' => 'Male',
            '{rider_address}' => 'Sample Address Line',
            '{rider_city}' => 'Dubai',
            '{rider_country}' => 'UAE',
            '{joining_date}' => '01-Jan-2024',
            '{designation}' => 'Delivery Rider',
            '{salary}' => '—',
            '{company_name}' => 'Sample Company',
            '{current_date}' => now()->format('d-M-Y'),
        ];
    }
}
