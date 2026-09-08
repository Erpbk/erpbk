<?php

namespace Tests\Unit;

use App\Models\AgreementTemplate;
use App\Services\Agreements\AgreementMpdfDocument;
use App\Services\Agreements\AgreementPdfService;
use Tests\TestCase;

class AgreementMpdfSmokeTest extends TestCase
{
    public function test_preview_pdf_returns_mpdf_document_with_pdf_header(): void
    {
        $template = AgreementTemplate::query()->find(9);
        if (! $template) {
            $this->markTestSkipped('Template 9 (new agreement test) not present in this DB');
        }

        /** @var AgreementPdfService $service */
        $service = app(AgreementPdfService::class);
        $pdf = $service->previewPdf($template, null, null, true);

        $this->assertInstanceOf(AgreementMpdfDocument::class, $pdf);
        $bytes = $pdf->output();
        $this->assertNotSame('', $bytes);
        $this->assertStringStartsWith('%PDF', $bytes);
        // Logical Arabic must be present in the HTML path (no LRO control chars).
        $html = $service->renderHtmlForModule(
            $template,
            'riders',
            new \App\Models\Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']),
            null,
            true,
            true,
            true
        );
        $this->assertStringContainsString('نؤكد', $html);
        $this->assertStringNotContainsString("\u{202D}", $html);
        $this->assertStringNotContainsString('bidi-override', $html);
        $this->assertStringContainsString('direction: rtl', $html);
    }
}
