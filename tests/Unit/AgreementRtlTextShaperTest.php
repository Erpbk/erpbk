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
        $this->assertMatchesRegularExpression('/<bdo\b[^>]*\bdir="ltr"[^>]*\bclass="[^"]*agreement-ar/i', $result['html']);
        $this->assertStringContainsString("\u{202D}", $result['html'], 'Shaped lines must start with Unicode LRO');
        $this->assertStringContainsString("\u{202C}", $result['html'], 'Shaped lines must end with Unicode PDF');
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
        $this->assertMatchesRegularExpression('/<bdo\b[^>]*class="[^"]*agreement-ar/i', $result['html']);
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
        // Block-level dir is stripped (avoids double-reverse); <bdo dir="ltr"> is intentional.
        $this->assertStringNotContainsString('dir="rtl"', $result['html']);
        $this->assertDoesNotMatchRegularExpression(
            '/<(p|div|h[1-6]|li|td|th|blockquote)\b[^>]*\bdir="/i',
            $result['html'],
            'Block elements must not keep editor dir attributes'
        );
        $this->assertMatchesRegularExpression('/<bdo\b[^>]*\bdir="ltr"/i', $result['html']);
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

    public function test_mixed_arabic_preserves_hyphenated_phone_id_order(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $phone = '784-1988-6268395-4';
        $html = '<p dir="rtl">رقم الهاتف '.$phone.'</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString($phone, $result['html'], 'Hyphenated phone/ID must keep editor digit order');
        $this->assertStringNotContainsString('4-6268395-1988-784', $result['html']);
        $this->assertStringContainsString('agreement-ar', $result['html']);
    }

    public function test_mixed_arabic_preserves_latin_chassis_and_plate(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $chassis = 'MD2A11B009NR821947';
        $plate = 'ABC-123';
        $html = '<p dir="rtl">الشاصي: '.$chassis.' واللوحة: '.$plate.'</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString($chassis, $result['html'], 'Chassis Latin/digits must stay in sequence');
        $this->assertStringContainsString($plate, $result['html'], 'Plate token must stay intact');
        // Presentation forms still applied to Arabic label words.
        $this->assertTrue(
            preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $result['html']) === 1
            || $shaper->containsArabicScript($result['html'])
        );
    }

    public function test_long_mixed_line_width_wraps_without_splitting_ltr_token(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(50.0, 11.0);
        $shaper->setWidthMeasurer(static function (string $text, float $sizePt, ?string $fontFile): float {
            return mb_strlen($text, 'UTF-8') * 1.0;
        });

        $id = '784-1988-6268395-4';
        $html = '<p>'.str_repeat('هذا نص عربي ', 8).$id.' '.str_repeat('طويل للاختبار ', 8).'</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertMatchesRegularExpression('/<br\s*\/?>/i', $result['html']);
        $this->assertStringContainsString($id, $result['html'], 'LTR ID must not be segment-reversed across wrap');
        $this->assertStringNotContainsString('4-6268395-1988-784', $result['html']);
    }

    public function test_chassis_label_phrase_order_not_word_swapped(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $arabic = new Arabic();
        $chassis = 'MD2A11CX3RCG00469';
        $label = 'رقم الشاصي';
        $correctVisual = $arabic->utf8Glyphs($label, 999999, false);
        $word1 = $arabic->utf8Glyphs('رقم', 999999, false);
        $word2 = $arabic->utf8Glyphs('الشاصي', 999999, false);
        $swappedVisual = $word1.' '.$word2;

        $html = '<p dir="rtl">'.$label.' : '.$chassis.'</p>';
        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString($chassis, $result['html'], 'Chassis Latin must stay intact');
        $this->assertStringContainsString(
            $correctVisual,
            $result['html'],
            'Label must match a single utf8Glyphs of the full Arabic phrase (not word-swapped)'
        );
        if ($swappedVisual !== $correctVisual) {
            $this->assertStringNotContainsString(
                $swappedVisual,
                $result['html'],
                'Must not contain word-swapped presentation of رقم الشاصي'
            );
        }

        $pos1 = mb_strpos($result['html'], $word1);
        $pos2 = mb_strpos($result['html'], $word2);
        $cPos1 = mb_strpos($correctVisual, $word1);
        $cPos2 = mb_strpos($correctVisual, $word2);
        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($cPos1);
        $this->assertNotFalse($cPos2);
        $this->assertSame(
            $cPos1 < $cPos2,
            $pos1 < $pos2,
            'رقم and الشاصي must keep correct relative order vs full-phrase utf8Glyphs'
        );
    }

    public function test_plate_line_preserves_ltr_token_and_arabic_label_order(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $arabic = new Arabic();
        $plate = '2/30178';
        $label = 'رقم اللوحة';
        $correctVisual = $arabic->utf8Glyphs($label, 999999, false);

        $html = '<p dir="rtl">'.$label.' :'.$plate.'</p>';
        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertStringContainsString($plate, $result['html']);
        $this->assertStringContainsString($correctVisual, $result['html']);
        $this->assertStringNotContainsString('87103/2', $result['html']);
    }

    public function test_editor_sample_body_keeps_key_phrase_and_ltr_order(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $arabic = new Arabic();

        $phone = '784-1988-6268395-4';
        $chassis = 'MD2A11CX3RCG00469';
        $plate = '2/30178';

        // Shortened editor-style RTL body covering the reported scramble cases.
        $html = '<p dir="rtl">'
            .'اسم المشتري : أحمد'
            .'<br>رقم الهاتف '.$phone
            .'<br>رقم الشاصي : '.$chassis
            .'<br>رقم اللوحة :'.$plate
            .'</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString($phone, $result['html']);
        $this->assertStringNotContainsString('4-6268395-1988-784', $result['html']);
        $this->assertStringContainsString($chassis, $result['html']);
        $this->assertStringContainsString($plate, $result['html']);

        $chassisLabelVisual = $arabic->utf8Glyphs('رقم الشاصي', 999999, false);
        $plateLabelVisual = $arabic->utf8Glyphs('رقم اللوحة', 999999, false);
        $this->assertStringContainsString($chassisLabelVisual, $result['html']);
        $this->assertStringContainsString($plateLabelVisual, $result['html']);

        // Word-swapped forms from the old run-reverse path must not appear.
        $swappedChassis = $arabic->utf8Glyphs('رقم', 999999, false).' '.$arabic->utf8Glyphs('الشاصي', 999999, false);
        if ($swappedChassis !== $chassisLabelVisual) {
            $this->assertStringNotContainsString($swappedChassis, $result['html']);
        }

        // Name fragment should still be shaped (presentation forms present).
        $this->assertTrue(
            preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $result['html']) === 1
        );
    }

    public function test_inline_direction_rtl_style_stripped_keeps_text_align_and_shaped_glyphs(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(500.0, 16.0);
        $html = '<p dir="rtl" style="direction: rtl; text-align: right;">نؤكد أننا قد استلمنا موافقة السيد رضوان غلزار، والذي يمكن التواصل معه على رقم هاتف الشركة</p>';

        $result = $shaper->shapeHtmlForPdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertFalse($result['rtl']);
        $this->assertStringContainsString('agreement-ar', $result['html']);
        $this->assertStringContainsString('agreement-ar-block', $result['html']);
        $this->assertStringNotContainsString('dir="rtl"', $result['html']);
        // TinyMCE direction must not survive — Dompdf would reverse visual glyphs again.
        $this->assertDoesNotMatchRegularExpression('/direction\s*:/i', $result['html']);
        $this->assertDoesNotMatchRegularExpression('/unicode-bidi\s*:/i', $result['html']);
        $this->assertMatchesRegularExpression('/text-align\s*:\s*right/i', $result['html']);
        $this->assertTrue(
            preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $result['html']) === 1
            || $shaper->containsArabicScript($result['html']),
            'Arabic must be shaped to presentation forms (agreement-ar glyphs)'
        );
    }

    public function test_pure_arabic_phrase_matches_full_utf8_glyphs(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $arabic = new Arabic();
        $logical = 'رقم الشاصي';
        $expectedVisual = $arabic->utf8Glyphs($logical, 999999, false);

        $result = $shaper->shapeHtmlForPdf('<p>'.$logical.'</p>');

        $this->assertStringContainsString($expectedVisual, $result['html']);
        $swapped = $arabic->utf8Glyphs('رقم', 999999, false).' '.$arabic->utf8Glyphs('الشاصي', 999999, false);
        if ($swapped !== $expectedVisual) {
            $this->assertStringNotContainsString($swapped, $result['html']);
        }
    }
    public function test_letterhead_css_uses_normal_rtl_for_mpdf_arabic(): void
    {
        $blade = file_get_contents(resource_path('views/agreements/pdf/letterhead.blade.php'));
        $this->assertNotFalse($blade);

        // mPDF paints pre-joined presentation forms with bidi-override, right aligned.
        $this->assertStringContainsString("pdfEngine === 'mpdf'", $blade);
        $this->assertStringContainsString('unicode-bidi: bidi-override', $blade);
        $this->assertStringContainsString('font-weight: bold', $blade);
        $this->assertStringNotContainsString('font-weight: 700', $blade);
        $this->assertDoesNotMatchRegularExpression(
            '/\.agreement-ar-block,\s*\[dir="rtl"\]\s*\{[^}]*text-align\s*:\s*right/',
            $blade
        );
    }

    public function test_shaped_html_uses_bdo_and_unicode_lro_pdf(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $shaper->configureForPdf(2000.0, 11.0);
        $result = $shaper->shapeHtmlForPdf('<p>مرحبا بالعالم</p>');

        $this->assertTrue($result['has_arabic']);
        $this->assertMatchesRegularExpression(
            '/<bdo\b[^>]*dir="ltr"[^>]*class="[^"]*agreement-ar[^"]*"[^>]*>/i',
            $result['html']
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<span\b[^>]*class="[^"]*agreement-ar[^"]*"[^>]*>/i',
            $result['html'],
            'Prefer <bdo> over <span> for agreement-ar wraps'
        );
        $this->assertStringContainsString("\u{202D}", $result['html']);
        $this->assertStringContainsString("\u{202C}", $result['html']);
        // LRO immediately before presentation-form / Arabic glyphs inside bdo
        $this->assertMatchesRegularExpression(
            '/<bdo\b[^>]*>\x{202D}/u',
            $result['html']
        );
    }

    public function test_prepare_logical_html_for_mpdf_keeps_unicode_no_bdo_lro(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p dir="rtl" style="direction: rtl; text-align: right;">نؤكد أننا قد استلمنا موافقة السيد</p>';

        $result = $shaper->prepareLogicalHtmlForMpdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertFalse($result['rtl']);
        $this->assertStringContainsString('agreement-ar-block', $result['html']);
        $this->assertStringContainsString('dir="rtl"', $result['html']);
        $this->assertStringNotContainsString("\u{202D}", $result['html']);
        $this->assertStringNotContainsString("\u{202C}", $result['html']);
        $this->assertMatchesRegularExpression(
            '/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u',
            $result['html'],
            'Arabic letters must be presentation forms so mPDF can draw them joined'
        );
    }
    public function test_prepare_logical_html_for_mpdf_wraps_phone_chassis_plate(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $phone = '784-1988-6268395-4';
        $chassis = 'MD2A11CX3RCG00469';
        $plate = '2/30178';
        $html = '<p dir="rtl">رقم الهاتف '.$phone.'<br>رقم الشاصي : '.$chassis.'<br>رقم اللوحة :'.$plate.'</p>';

        $result = $shaper->prepareLogicalHtmlForMpdf($html);

        $this->assertTrue($result['has_arabic']);
        $this->assertFalse($result['rtl']);
        $this->assertStringContainsString($phone, $result['html']);
        $this->assertStringContainsString($chassis, $result['html']);
        $this->assertStringContainsString($plate, $result['html']);
        $this->assertStringNotContainsString('4-6268395-1988-784', $result['html']);
        $this->assertStringNotContainsString("\u{202D}", $result['html']);
        $this->assertMatchesRegularExpression(
            '/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u',
            $result['html']
        );
    }


    public function test_mpdf_chassis_reversed_label_normalized_plate_unchanged(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $chassis = 'MD2A11CX3RCG00469';
        $plate = '2/30178';
        $phone = '784-1988-6268395-4';

        // Stored TinyMCE RTL: chassis value-before-label (styled); plate already label-first with styles.
        $html = '<p dir="rtl">'
            .'<span style="font-size: 16pt; font-family: \'Noto Naskh Arabic\';">'
            .'<span style="font-family: Calibri;">'.$chassis.'</span> <strong>:</strong></span> '
            .'<strong style="font-size: 16pt; font-family: \'Noto Naskh Arabic\';">رقم الشاصي</strong>'
            .'<br><span style="font-size: 16pt; font-family: \'Noto Naskh Arabic\';">'
            .'<strong>رقم اللوحة <span>:</span></strong>'
            .'<span style="font-family: Calibri;">'.$plate.'</span></span>'
            .'<br>نؤكد التواصل على رقم هاتف الشركة '.$phone.' للمزيد'
            .'</p>';

        $result = $shaper->prepareLogicalHtmlForMpdf($html);
        $out = $result['html'];

        $this->assertTrue($result['has_arabic']);
        $this->assertStringContainsString($chassis, $out);
        $this->assertStringContainsString($plate, $out);
        $this->assertStringContainsString($phone, $out);
        $this->assertStringNotContainsString('4-6268395-1988-784', $out);
        $this->assertMatchesRegularExpression(
            '/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u',
            $out
        );

        // Rebuilt reversed field line must keep TinyMCE body font-size from the Noto source span.
        $this->assertMatchesRegularExpression(
            '/font-size\s*:\s*16pt/i',
            $out,
            'Rebuilt chassis/field line must preserve font-size from source Noto span'
        );
        // Plate line styles were never rebuilt away — still present.
        $this->assertStringContainsString('Noto Naskh Arabic', $out);
    }

    public function test_english_colon_line_is_not_treated_as_reversed_arabic_field(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p dir="rtl">To: Dubai Police / الجهة المختصة</p>';

        $result = $shaper->prepareLogicalHtmlForMpdf($html);

        $this->assertStringContainsString('To', $result['html']);
        $to = mb_strpos($result['html'], 'To');
        $police = mb_strpos($result['html'], 'Dubai');
        $this->assertNotFalse($to);
        $this->assertNotFalse($police);
        $this->assertLessThan($police, $to);
    }

    public function test_mixed_plate_line_keeps_colon_and_digits_after_arabic(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p style="text-align: left;">Plate number / رقم اللوحة : 2676</p>'
            .'<p style="text-align: left;">Chassis number / رقم الشاصي : MD2A11CX3RCG00469</p>';

        $out = $shaper->prepareLogicalHtmlForMpdf($html)['html'];

        $this->assertMatchesRegularExpression('/Plate number \/ .+ : <bdi dir="ltr">2676<\/bdi>/u', $out);
        $this->assertMatchesRegularExpression('/Chassis number \/ .+ : <bdi dir="ltr">MD2A11CX3RCG00469<\/bdi>/u', $out);
        $this->assertStringNotContainsString('Plate number /  :', $out);
        $this->assertStringNotContainsString('Plate number / :', $out);
        $this->assertStringNotContainsString('Chassis number /  :', $out);
        $this->assertStringNotContainsString('Chassis number / :', $out);
    }

    public function test_english_led_line_keeps_arabic_after_the_slash(): void
    {
        $shaper = new AgreementRtlTextShaper();
        $html = '<p style="text-align: left;">Subject: Authorization / تفويض لإتمام إجراءات الإفراج عن الدراجة النارية</p>'
            .'<p style="text-align: left;">Thank you for your cooperation. / نشكر لكم تعاونكم ومساعدتكم في حل هذه المشكلة.</p>';

        $out = $shaper->prepareLogicalHtmlForMpdf($html)['html'];

        $this->assertStringNotContainsString('dir="rtl"', $out);
        $this->assertStringContainsString('Subject: Authorization /', $out);
        $this->assertStringContainsString('Thank you for your cooperation. /', $out);

        $subject = mb_strpos($out, 'Subject: Authorization /');
        $thanks = mb_strpos($out, 'Thank you for your cooperation. /');
        preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $out, $forms, PREG_OFFSET_CAPTURE);
        $this->assertNotFalse($subject);
        $this->assertNotFalse($thanks);
        $this->assertNotSame([], $forms);
        $this->assertLessThan($forms[0][1], $subject);
    }
}
