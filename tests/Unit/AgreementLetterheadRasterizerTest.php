<?php

namespace Tests\Unit;

use App\Services\Agreements\AgreementLetterheadRasterizer;
use App\Services\Agreements\AgreementPdfBranding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AgreementLetterheadRasterizerTest extends TestCase
{
    public function test_stores_png_letterhead_on_public_disk(): void
    {
        Storage::fake('public');

        $tmp = tempnam(sys_get_temp_dir(), 'lh');
        $image = imagecreatetruecolor(40, 60);
        imagefilledrectangle($image, 0, 0, 39, 59, imagecolorallocate($image, 180, 20, 20));
        imagepng($image, $tmp);
        imagedestroy($image);

        $file = new UploadedFile($tmp, 'letterhead.png', 'image/png', null, true);
        $path = $this->app->make(AgreementLetterheadRasterizer::class)->store($file, 9);

        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertStringStartsWith('agreement-letterheads/9/', $path);
        @unlink($tmp);
    }

    public function test_unreadable_pdf_without_converter_throws(): void
    {
        Storage::fake('public');

        $tmp = tempnam(sys_get_temp_dir(), 'lhpdf');
        file_put_contents($tmp, "%PDF-1.1\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
        $file = new UploadedFile($tmp, 'letterhead.pdf', 'application/pdf', null, true);

        try {
            $this->app->make(AgreementLetterheadRasterizer::class)->store($file, 9);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PDF', $e->getMessage());
        } finally {
            @unlink($tmp);
        }
    }

    public function test_page_chrome_uses_uploaded_design_instead_of_company_header(): void
    {
        $withDesign = view('agreements.pdf.partials.page-chrome', [
            'branding' => [
                'letterhead_src' => 'data:image/png;base64,AAA',
                'name' => 'Fast Delivery',
            ],
            'pageWidthMm' => 210,
            'pageHeightMm' => 297,
        ])->render();

        $this->assertStringContainsString('page-letterhead-design', $withDesign);
        $this->assertStringNotContainsString('page-header-logo', $withDesign);

        $generated = view('agreements.pdf.partials.page-chrome', [
            'branding' => [
                'letterhead_src' => null,
                'name' => 'Fast Delivery',
                'email' => 'office@example.com',
                'phone' => '',
                'address' => '',
                'secondary_color' => '#2563eb',
            ],
            'pageWidthMm' => 210,
            'pageHeightMm' => 297,
        ])->render();

        $this->assertStringContainsString('page-header', $generated);
        $this->assertStringNotContainsString('page-letterhead-design', $generated);
    }

    public function test_branding_skips_uploaded_src_when_category_has_no_file(): void
    {
        $branding = $this->app->make(AgreementPdfBranding::class)->withUploadedLetterhead(
            ['name' => 'Co'],
            null
        );

        $this->assertNull($branding['letterhead_src']);
        $this->assertFalse($branding['has_uploaded_letterhead']);
    }
}
