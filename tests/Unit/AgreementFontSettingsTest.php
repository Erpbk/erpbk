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

    public function test_cached_faces_include_calibri_even_without_windows_fonts(): void
    {
        $fonts = $this->app->make(AgreementFontSettings::class);
        $faces = $fonts->cachedFaces();
        $families = array_unique(array_column($faces, 'family'));

        $this->assertContains('Calibri', $families);
        $this->assertStringContainsString("font-family:'Calibri'", $fonts->browserFontFaceCss());
        $this->assertNotNull($fonts->cachedFontPath('calibri-normal-normal.ttf'));

        $calibri = collect($faces)->first(
            fn (array $face): bool => $face['family'] === 'Calibri'
                && $face['weight'] === 'normal'
                && $face['style'] === 'normal'
        );
        $this->assertNotNull($calibri);
        $bundled = $fonts->bundledFontDirectory() . 'Carlito-Regular.ttf';
        $this->assertFileExists($bundled);
        $this->assertSame(filesize($bundled), filesize($calibri['path']));
    }

    public function test_stale_dompdf_font_cache_is_refreshed_when_source_path_changes(): void
    {
        $fonts = $this->app->make(AgreementFontSettings::class);
        $faces = $fonts->cachedFaces();
        $calibri = collect($faces)->first(
            fn (array $face): bool => $face['family'] === 'Calibri'
                && $face['weight'] === 'normal'
                && $face['style'] === 'normal'
        );
        $this->assertNotNull($calibri);

        $dir = storage_path('fonts');
        $installedFile = $dir . DIRECTORY_SEPARATOR . 'installed-fonts.json';
        $staleBase = 'calibri_normal_staletesttoken';
        $staleTtf = $dir . DIRECTORY_SEPARATOR . $staleBase . '.ttf';
        $staleUfm = $dir . DIRECTORY_SEPARATOR . $staleBase . '.ufm';

        file_put_contents($staleTtf, 'stale-font-bytes');
        file_put_contents($staleUfm, "FontName stale\n");

        $installed = [
            'calibri' => [
                'normal' => $staleBase,
            ],
        ];
        file_put_contents($installedFile, json_encode($installed, JSON_PRETTY_PRINT));

        $fonts->refreshDompdfFontRegistry([$calibri]);

        $this->assertFileDoesNotExist($staleTtf);
        $this->assertFileDoesNotExist($staleUfm);
        $refreshed = json_decode((string) file_get_contents($installedFile), true);
        $this->assertArrayNotHasKey('calibri', $refreshed);
    }
}
