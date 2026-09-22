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

        $rider = new \App\Models\Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']);
        $html = $service->renderHtmlForModule(
            $template,
            'riders',
            $rider,
            null,
            true,
            true,
            true
        );

        $this->assertMatchesRegularExpression('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $html);
        $this->assertStringNotContainsString("\u{202D}", $html);
        $this->assertStringContainsString('unicode-bidi: bidi-override', $html);

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

    public function test_default_letterhead_preview_html_does_not_use_fixed_body_overlay(): void
    {
        $template = AgreementTemplate::query()->with('category')->find(10);
        if (! $template) {
            $this->markTestSkipped('Template 10 (General browser check) not present in this DB');
        }
        if (($template->category?->letterheadMode() ?? '') !== 'default') {
            $this->markTestSkipped('Template 10 category is not default letterhead');
        }

        /** @var AgreementPdfService $service */
        $service = app(AgreementPdfService::class);
        $rider = new \App\Models\Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']);
        $html = $service->renderHtmlForModule($template, 'riders', $rider, null, true, true, true);

        $this->assertStringNotContainsString('letterhead-overlay--fixed', $html);
        $this->assertStringContainsString('htmlpageheader name="agreementDefaultHeader"', $html);
        $this->assertStringContainsString('This agreement is made between', $html);
    }

    public function test_default_letterhead_keeps_short_agreement_on_one_page(): void
    {
        $template = AgreementTemplate::query()->with('category')->find(10);
        if (! $template) {
            $this->markTestSkipped('Template 10 (General browser check) not present in this DB');
        }
        if (($template->category?->letterheadMode() ?? '') !== 'default') {
            $this->markTestSkipped('Template 10 category is not default letterhead');
        }

        /** @var AgreementPdfService $service */
        $service = app(AgreementPdfService::class);
        $pdf = $service->previewPdf($template, null, null, true);
        $bytes = $pdf->output();

        $this->assertStringStartsWith('%PDF', $bytes);
        preg_match_all('/\/Type\s*\/Page[^s]/', $bytes, $pages);
        $this->assertSame(1, count($pages[0]), 'Default letterhead must not push short content onto page 2');
        $this->assertSame(1, $pdf->getMpdf()->page);
        $this->assertGreaterThan(0, preg_match_all('/\/Subtype\s*\/Image/', $bytes), 'Company logo must paint on the same page');
    }

    public function test_heading_styles_do_not_force_following_content_onto_a_new_page(): void
    {
        $template = AgreementTemplate::query()->with('category')->find(10);
        if (! $template) {
            $this->markTestSkipped('Template 10 (General browser check) not present in this DB');
        }

        /** @var AgreementPdfService $service */
        $service = app(AgreementPdfService::class);
        $rider = new \App\Models\Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']);
        $html = $service->renderHtmlForModule($template, 'riders', $rider, null, true, true, true);
        $html = preg_replace(
            '/<div class="content">/i',
            '<div class="content"><h1>Heading One</h1><h2>Heading Two</h2><h3>Heading Three</h3>',
            $html,
            1
        ) ?? $html;

        $this->assertStringContainsString('<h1>Heading One</h1>', $html);

        $ref = new \ReflectionClass($service);
        $build = $ref->getMethod('buildMpdf');
        $build->setAccessible(true);
        $pdf = $build->invoke($service, $html, $template->category, true);

        $this->assertSame(1, $pdf->getMpdf()->page, 'H1/H2/H3 must not start a new page for a short agreement');
    }
}