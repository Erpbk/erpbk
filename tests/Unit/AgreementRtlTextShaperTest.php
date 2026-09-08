<?php

namespace Tests\Unit;

use App\Services\Agreements\AgreementRtlTextShaper;
use Tests\TestCase;

class AgreementRtlTextShaperTest extends TestCase
{
    public function test_latin_html_is_unchanged(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p>Hello world — Contract Highlights</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl']);
        $this->assertFalse($result['has_arabic']);
        $this->assertSame($html, $result['html']);
    }

    public function test_arabic_script_is_detected_and_shaped(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p>مرحبا بالعالم</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl'], 'Document must not be wholly RTL');
        $this->assertTrue($result['has_arabic']);
        $this->assertNotSame($html, $result['html']);
        $this->assertStringContainsString('<p', $result['html']);
        $this->assertStringContainsString('agreement-ar', $result['html']);
        $this->assertStringContainsString('agreement-ar-block', $result['html']);
        $this->assertTrue(
            $shaper->containsArabicScript($result['html'])
            || preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $result['html']) === 1
        );
    }

    public function test_arabic_script_sample_is_shaped_without_document_rtl(): void
    {
        $shaper = new AgreementRtlTextShaper();
        // Arabic-script sample (not an Urdu product-language path).
        $html = '<p>هذا نص بالعربية للاختبار.</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl']);
        $this->assertTrue($result['has_arabic']);
        $this->assertNotSame($html, $result['html']);
        $this->assertStringContainsString('agreement-ar', $result['html']);
    }

    public function test_mixed_html_preserves_tags_and_english_ltr(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p><strong>عنوان</strong>: Section 1</p><table><tr><td>رقم</td><td>42</td></tr></table>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl'], 'Mixed EN+AR must not flag whole-document RTL');
        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString('<strong>', $result['html']);
        $this->assertStringContainsString('</strong>', $result['html']);
        $this->assertStringContainsString('<table>', $result['html']);
        $this->assertStringContainsString('42', $result['html']);
        $this->assertStringContainsString('Section 1', $result['html']);
        $this->assertStringContainsString('agreement-ar', $result['html']);
    }

    public function test_editor_dir_rtl_becomes_ar_block_without_dir_attr(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p dir="rtl">مرحبا</p><p dir="ltr">Hello English</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl']);
        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString('agreement-ar-block', $result['html']);
        $this->assertStringContainsString('agreement-ltr-block', $result['html']);
        $this->assertStringNotContainsString('dir="rtl"', $result['html']);
        $this->assertStringNotContainsString('dir="ltr"', $result['html']);
        $this->assertStringContainsString('Hello English', $result['html']);
    }

    public function test_long_arabic_gets_visual_line_breaks(): void
    {
        $shaper = new AgreementRtlTextShaper();
        // Long enough that Ar-PHP soft-wraps near PDF_ARABIC_LINE_CHARS (~80).
        $html = '<p>'.str_repeat('هذا نص عربي طويل للاختبار ', 20).'</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl']);
        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString('agreement-ar', $result['html']);
        // Soft wraps become HTML breaks so Dompdf keeps visual top→bottom order.
        $this->assertMatchesRegularExpression('/<br\s*\/?>/i', $result['html']);
        $this->assertGreaterThanOrEqual(
            2,
            preg_match_all('/<br\s*\/?>/i', $result['html']),
            'Long Arabic paragraph should produce multiple visual lines'
        );
        // Raw newlines from utf8Glyphs must not leak into HTML output.
        $this->assertStringNotContainsString("\n", $result['html']);
    }

    public function test_vocalized_arabic_with_spaces_does_not_crash(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p>التَّبِيعَةُ جَمِيلَةٌ جِدًّا. فِي الصَّباحِ، تَشْرُمُ الشَّمْسُ</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl']);
        $this->assertTrue($result['has_arabic']);
        $this->assertNotSame($html, $result['html']);
        $this->assertStringContainsString('<p', $result['html']);
    }
}