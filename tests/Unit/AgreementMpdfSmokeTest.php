<?php

namespace Tests\Unit;

use App\Models\AgreementTemplate;
use App\Services\Agreements\AgreementMpdfDocument;
use App\Services\Agreements\AgreementPdfService;
use Tests\TestCase;

class AgreementMpdfSmokeTest extends TestCase
{
    public function test_mpdf_arabic_font_remap_prefers_noto_when_available(): void
    {
        /** @var AgreementPdfService $service */
        $service = app(AgreementPdfService::class);

        $sample = <<<'HTML'
<p style="font-family: Amiri, serif">a</p>
<span style="font-family: 'Noto Naskh Arabic'">b</span>
<div style="font-family: Scheherazade New, Amiri">c</div>
<style>.ar{font-family:"Amiri"}</style>
HTML;

        $out = $service->forceLateefFontsForMpdfHtml($sample);

        $this->assertStringNotContainsString('Amiri', $out);
        $this->assertStringNotContainsString('Noto Naskh', $out);
        $this->assertStringNotContainsString('Scheherazade', $out);

        $notoFile = app(\App\Services\Agreements\AgreementFontSettings::class)
            ->bundledFontDirectory().'NotoNaskhArabic-Regular.ttf';
        $expected = is_readable($notoFile) ? 'notonaskharabic' : 'lateef';
        $this->assertStringContainsString('font-family: '.$expected, $out);
        $withoutTarget = preg_replace('/font-family\s*:\s*'.preg_quote($expected, '/').'\b/i', '', $out) ?? $out;
        $this->assertDoesNotMatchRegularExpression('/font-family\s*:/i', $withoutTarget);
    }

    public function test_preview_pdf_returns_mpdf_document_with_pdf_header(): void
    {
        $template = AgreementTemplate::query()->find(9);
        if (! $template) {
            $this->markTestSkipped('Template 9 (new agreement test) not present in this DB');
        }

        /** @var AgreementPdfService $service */
        $service = app(AgreementPdfService::class);

        // Force mPDF path (Chrome remains preferred in production resolvePdfEngine).
        $rider = new \App\Models\Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']);
        $html = $service->renderHtmlForModule(
            $template,
            'riders',
            $rider,
            null,
            true,
            true,
            true,
            'mpdf'
        );

        $this->assertStringContainsString('نؤكد', $html);
        $this->assertStringNotContainsString("\u{202D}", $html);
        $this->assertStringNotContainsString('unicode-bidi: bidi-override', $html);
        $this->assertStringContainsString('direction: rtl', $html);

        $notoFile = app(\App\Services\Agreements\AgreementFontSettings::class)
            ->bundledFontDirectory().'NotoNaskhArabic-Regular.ttf';
        $expected = is_readable($notoFile) ? 'notonaskharabic' : 'lateef';
        $this->assertStringContainsString('font-family: '.$expected, $html);

        $ref = new \ReflectionClass($service);
        $build = $ref->getMethod('buildMpdf');
        $build->setAccessible(true);
        $pdf = $build->invoke($service, $html, $template->category, true);

        $this->assertInstanceOf(AgreementMpdfDocument::class, $pdf);
        $bytes = $pdf->output();
        $this->assertNotSame('', $bytes);
        $this->assertStringStartsWith('%PDF', $bytes);
    }
}