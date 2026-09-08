<?php

namespace Tests\Unit;

use App\Services\Agreements\AgreementRtlTextShaper;
use ArPHP\I18N\Arabic;
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
        // Direction is applied via align/CSS classes, not HTML dir (avoids double-reverse).
        $this->assertStringNotContainsString('dir="rtl"', $result['html']);
        $this->assertStringNotContainsString('dir="ltr"', $result['html']);
        $this->assertStringContainsString('Hello English', $result['html']);
    }

    public function test_long_arabic_uses_measure_based_breaks_not_char_constant(): void
    {
        $shaper = new AgreementRtlTextShaper();
        // Narrow column forces multiple lines; measurer uses char count as a stand-in for width.
        $shaper->configureForPdf(40.0, 11.0);
        $shaper->setWidthMeasurer(static function (string $text, float $sizePt, ?string $fontFile): float {
            return mb_strlen($text, 'UTF-8') * 1.0; // 1pt per glyph → width 40 packs ~40 glyphs
        });

        $html = '<p>'.str_repeat('هذا نص عربي طويل للاختبار ', 20).'</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertFalse($result['rtl']);
        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString('agreement-ar', $result['html']);
        $this->assertStringContainsString('agreement-ar-block', $result['html']);
        $this->assertMatchesRegularExpression('/<br\s*\/?>/i', $result['html'], 'Width packing should emit <br /> lines');
        $this->assertTrue(
            $shaper->containsArabicScript($result['html'])
            || preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $result['html']) === 1,
            'Long Arabic should still be shaped to presentation forms'
        );
    }

    public function test_wide_column_keeps_short_arabic_on_one_line(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $shaper->setWidthMeasurer(static function (string $text, float $sizePt, ?string $fontFile): float {
            return mb_strlen($text, 'UTF-8') * 1.0;
        });

        $html = '<p>مرحبا بالعالم</p>';
        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertDoesNotMatchRegularExpression('/<br\s*\/?>/i', $result['html']);
    }

    public function test_shaped_glyphs_stay_in_visual_order_not_reversed(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $arabic = new Arabic();
        $logical = 'مرحبا';
        $expectedVisual = $arabic->utf8Glyphs($logical, 999999, false);

        $result = $shaper->shapeHtmlForPdf('<p>'.$logical.'</p>');

        $this->assertStringContainsString($expectedVisual, $result['html']);
        $reversed = implode('', array_reverse(preg_split('//u', $expectedVisual, -1, PREG_SPLIT_NO_EMPTY) ?: []));
        if ($reversed !== $expectedVisual) {
            $this->assertStringNotContainsString(
                '>'.$reversed.'<',
                $result['html'],
                'Must not reverse visual-order presentation forms'
            );
        }
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
