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

    public function test_page_chrome_none_skips_header_and_design(): void
    {
        $html = view('agreements.pdf.partials.page-chrome', [
            'branding' => [
                'letterhead_src' => null,
                'letterhead_mode' => 'none',
                'name' => 'Fast Delivery',
            ],
            'pageWidthMm' => 210,
            'pageHeightMm' => 297,
        ])->render();

        $this->assertStringNotContainsString('page-letterhead-design', $html);
        $this->assertStringNotContainsString('page-header', $html);
        $this->assertStringNotContainsString('page-watermark', $html);
    }

    public function test_page_chrome_renders_selected_watermark(): void
    {
        $html = view('agreements.pdf.partials.page-chrome', [
            'branding' => [
                'letterhead_src' => null,
                'letterhead_mode' => 'none',
                'watermark_src' => 'data:image/png;base64,AAA',
                'name' => 'Fast Delivery',
            ],
            'pageWidthMm' => 210,
            'pageHeightMm' => 297,
        ])->render();

        $this->assertStringContainsString('page-watermark', $html);
        $this->assertStringNotContainsString('page-header', $html);
    }

    public function test_stores_watermark_in_watermark_directory(): void
    {
        Storage::fake('public');

        $tmp = tempnam(sys_get_temp_dir(), 'wm');
        $image = imagecreatetruecolor(40, 40);
        imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 20, 20, 180));
        imagepng($image, $tmp);
        imagedestroy($image);

        $file = new UploadedFile($tmp, 'mark.png', 'image/png', null, true);
        $path = $this->app->make(AgreementLetterheadRasterizer::class)->store($file, 9, 'watermark');

        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertStringStartsWith('agreement-watermarks/9/', $path);
        @unlink($tmp);
    }

    public function test_branding_skips_uploaded_src_when_category_has_no_file(): void
    {
        $branding = $this->app->make(AgreementPdfBranding::class)->withUploadedLetterhead(
            ['name' => 'Co'],
            null
        );

        $this->assertNull($branding['letterhead_src']);
        $this->assertFalse($branding['has_uploaded_letterhead']);
        $this->assertNull($branding['watermark_src']);
    }

    public function test_default_watermark_uses_company_logo(): void
    {
        $category = new \App\Models\AgreementCategory(['watermark_mode' => 'default']);
        $src = $this->app->make(AgreementPdfBranding::class)->resolvedWatermarkSrc(
            ['logo_src' => 'data:image/png;base64,LOGO'],
            $category
        );

        $this->assertSame('data:image/png;base64,LOGO', $src);
    }

    public function test_letterhead_public_url_does_not_require_storage_symlink(): void
    {
        Storage::fake('public');

        $path = 'agreement-letterheads/1/demo.jpg';
        Storage::disk('public')->put($path, 'fake-image');

        $letterhead = new \App\Models\AgreementLetterhead([
            'name' => 'Demo',
            'path' => $path,
        ]);

        $url = $letterhead->publicUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString('storage/' . $path, $url);
    }

    public function test_inline_html_images_keeps_data_uris(): void
    {
        $html = '<p>Scan</p><p><img src="data:image/png;base64,AAA" alt="x"></p>';
        $out = $this->app->make(AgreementPdfBranding::class)->inlineHtmlImages($html);

        $this->assertStringContainsString('data:image/png;base64,AAA', $out);
        $this->assertStringContainsString('max-width:100%', $out);
    }
}
