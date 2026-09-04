<?php

namespace Tests\Unit;

use App\Services\Agreements\AgreementPdfBranding;
use Tests\TestCase;

class AgreementPdfBrandingTest extends TestCase
{
    public function test_inline_images_get_explicit_pixel_size_for_dompdf(): void
    {
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $html = $this->app->make(AgreementPdfBranding::class)->inlineHtmlImages(
            '<p><img src="'.$png.'" alt="scan" style="max-width:100%;height:auto;"></p>'
        );

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('width="1"', $html);
        $this->assertStringContainsString('height="1"', $html);
        $this->assertStringContainsString('width:1px', $html);
        $this->assertStringContainsString('height:1px', $html);
        $this->assertStringNotContainsString('height:auto', $html);
    }

    public function test_tinymce_relative_storage_paths_are_inlined_for_pdf(): void
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $this->assertNotFalse($bytes);

        $relative = 'company-logos/agreement-inline-test.png';
        \Illuminate\Support\Facades\Storage::disk('public')->put($relative, $bytes);

        try {
            $html = $this->app->make(AgreementPdfBranding::class)->inlineHtmlImages(
                '<p><img src="../../../../../storage/'.$relative.'" alt="Fast Delivery" width="149" height="135"></p>'
            );

            $this->assertStringContainsString('data:image/', $html);
            $this->assertStringNotContainsString('../../../../../storage/', $html);
            $this->assertStringContainsString('width="149"', $html);
        } finally {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);
        }
    }
}
