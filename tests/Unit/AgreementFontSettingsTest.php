<?php

namespace Tests\Unit;

use App\Services\Agreements\AgreementFontSettings;
use Tests\TestCase;

class AgreementFontSettingsTest extends TestCase
{
    public function test_word_fonts_are_mapped_onto_agreement_families(): void
    {
        $fonts = $this->app->make(AgreementFontSettings::class);

        $html = $fonts->normalizeHtml(
            '<p style="font-family: \'Calibri Light\', sans-serif; font-size: 12.0pt; mso-bidi-font-family: Arial;">Hello</p>'
        );

        $this->assertStringContainsString('font-family: Calibri', $html);
        $this->assertStringContainsString('font-size: 12pt', $html);
        $this->assertStringNotContainsString('mso-bidi-font-family', $html);
        $this->assertStringNotContainsString('Calibri Light', $html);
    }

    public function test_unknown_font_falls_back_to_default(): void
    {
        $fonts = $this->app->make(AgreementFontSettings::class);

        $html = $fonts->normalizeHtml('<span style="font-family: Comic Sans MS; font-size: 11.0pt;">Text</span>');

        $this->assertStringContainsString('font-family: Calibri', $html);
        $this->assertStringContainsString('font-size: 11pt', $html);
    }
}
