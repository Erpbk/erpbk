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
            'agreementLineHeight' => $this->fonts->lineHeight(),
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
            // Remap CSS font-family display names onto mPDF registered keys.
            $html = $this->remapFontFamiliesForMpdf($html);
            $html = preg_replace('/font-family:\s*[\'"]?(amiri|notonaskharabic|scheherazadenew|Amiri|Noto Naskh Arabic|Scheherazade New)[\'"]?/i', 'font-family: lateef', $html) ?? $html;
            // Prefer Noto Naskh (OTL) over Amiri for shaping.
            $html = str_ireplace(['font-family: Amiri', "font-family: 'Amiri'"], 'font-family: amiri', $html);
        }

        $size = $this->letterheadLayout->resolvedPageSize($category);
        $fontConfig = $this->mpdfFontConfig();

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $defaultFont = $this->mpdfFontKey($this->fonts->defaultFamily());
        if (! isset($fontConfig['fontdata'][$defaultFont])) {
            $defaultFont = isset($fontConfig['fontdata']['dejavusans']) ? 'dejavusans' : 'amiri';
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

        if ($withLetterhead) {
            $this->letterheadPainter->applyToMpdf($mpdf, $category);
        }

        // Large letterhead/logo data-URIs exceed default pcre.backtrack_limit in mPDF.
        $html = $this->materializeDataUrisForMpdf($html, $tempDir);
        $previousLimit = ini_get('pcre.backtrack_limit');
        @ini_set('pcre.backtrack_limit', (string) max(10000000, (int) $previousLimit));
        // Some Arabic TTFs trip undefined-offset notices inside mPDF GSUB/GPOS readers;
        // Laravel promotes those to ErrorException ? ignore only those font metrics notices.
        $previousReporting = error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
        set_error_handler(static function () {
            return true;
        });
        try {
            $mpdf->WriteHTML($html);
        } finally {
            restore_error_handler();
            error_reporting($previousReporting);
            if ($previousLimit !== false) {
                @ini_set('pcre.backtrack_limit', (string) $previousLimit);
            }
        }

        return new AgreementMpdfDocument($mpdf);
    }

    /**
     * @return array{fontDir: list<string>, fontdata: array<string, array<string, mixed>>}
     */
    private function mpdfFontConfig(): array
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

        // Core Arabic faces with OpenType layout enabled.
                $fontData['lateef'] = [
            'R' => 'Lateef-Regular.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ];
        // Modern Amiri/Noto/Scheherazade New trip unsupported GPOS/GSUB formats or OOM in mPDF OTL.
        $fontData['amiri'] = [
            'R' => 'Amiri-Regular.ttf',
            'B' => 'Amiri-Bold.ttf',
            'I' => 'Amiri-Italic.ttf',
            'BI' => 'Amiri-BoldItalic.ttf',
        ];
        $fontData['notonaskharabic'] = [
            'R' => 'NotoNaskhArabic-Regular.ttf',
            'B' => 'NotoNaskhArabic-Bold.ttf',
        ];
        $fontData['scheherazadenew'] = [
            'R' => 'ScheherazadeNew-Regular.ttf',
            'B' => 'ScheherazadeNew-Bold.ttf',
        ];

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
            // Prefer basename in a known fontDir when possible.
            $basename = basename($path);
            $dir = dirname($path);
            if (! in_array($dir, $fontDirs, true)) {
                $fontDirs[] = $dir;
            }
            $fontData[$key][$slot] = $basename;
            if ($key === 'lateef') {
                $fontData[$key]['useOTL'] = 0xFF;
                $fontData[$key]['useKashida'] = 75;
            }
        }

        return [
            'fontDir' => array_values(array_unique($fontDirs)),
            'fontdata' => $fontData,
        ];
    }

    private function mpdfFontKey(string $family): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $family) ?? $family);
    }

    private function mpdfRtlFamilyStackCss(): string
    {
        return 'lateef, amiri, notonaskharabic, scheherazadenew';
    }

    private function mpdfFamilyStackCss(string $defaultFamily, string $fallbackStack): string
    {
        $key = $this->mpdfFontKey($defaultFamily);
        $parts = [$key];
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
        $parts[] = 'dejavusans';

        return implode(', ', $parts);
    }

    private function remapFontFamiliesForMpdf(string $html): string
    {
        $map = [
            'Lateef' => 'lateef',
            'Amiri' => 'amiri',
            'Noto Naskh Arabic' => 'notonaskharabic',
            'Scheherazade New' => 'scheherazadenew',
            'Calibri' => 'calibri',
            'Arial' => 'arial',
            'Times New Roman' => 'timesnewroman',
            'Cambria' => 'cambria',
            'Courier New' => 'couriernew',
            'DejaVu Sans' => 'dejavusans',
        ];

        foreach ($map as $from => $to) {
            $html = str_ireplace(
                ["font-family: '{$from}'", "font-family:{$from}", "font-family: {$from}"],
                "font-family: {$to}",
                $html
            );
        }

        return $html;
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
            static function (array $m) use ($tempDir): string {
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
                }

                return 'src='.$quote.str_replace('\\', '/', $file).$quote;
            },
            $html
        ) ?? $html;
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
