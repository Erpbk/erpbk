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
        $this->assertSame($html, $result['html']);
    }

    public function test_arabic_script_is_detected_and_shaped(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p>مرحبا بالعالم</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['rtl']);
        $this->assertNotSame($html, $result['html']);
        $this->assertStringContainsString('<p>', $result['html']);
        $this->assertStringContainsString('</p>', $result['html']);
        // Presentation forms / shaped glyphs differ from logical Unicode input.
        $this->assertTrue(
            $shaper->containsArabicScript($result['html'])
            || preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $result['html']) === 1
        );
    }

    public function test_urdu_nastaliq_sample_is_shaped(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p>یہ ایک اردو معاہدہ ہے۔</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['rtl']);
        $this->assertNotSame($html, $result['html']);
    }

    public function test_mixed_html_preserves_tags(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p><strong>عنوان</strong>: Section 1</p><table><tr><td>رقم</td><td>42</td></tr></table>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['rtl']);
        $this->assertStringContainsString('<strong>', $result['html']);
        $this->assertStringContainsString('</strong>', $result['html']);
        $this->assertStringContainsString('<table>', $result['html']);
        $this->assertStringContainsString('42', $result['html']);
        $this->assertStringContainsString('Section 1', $result['html']);
    }

    public function test_long_arabic_does_not_inject_newlines(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p>'.str_repeat('هذا نص عربي طويل للاختبار ', 20).'</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['rtl']);
        $this->assertStringNotContainsString("\n", $result['html']);
    }

    public function test_vocalized_arabic_with_spaces_does_not_crash(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p>التَّبِيعَةُ جَمِيلَةٌ جِدًّا. فِي الصَّباحِ، تَشْرُمُ الشَّمْسُ</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['rtl']);
        $this->assertNotSame($html, $result['html']);
        $this->assertStringContainsString('<p>', $result['html']);
    }
}
