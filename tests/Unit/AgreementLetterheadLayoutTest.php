<?php

namespace Tests\Unit;

use App\Services\Agreements\AgreementLetterheadLayout;
use Tests\TestCase;

class AgreementLetterheadLayoutTest extends TestCase
{
    public function test_default_page_size_is_a4(): void
    {
        $layout = $this->app->make(AgreementLetterheadLayout::class);
        $size = $layout->pageSizeByKey(null);

        $this->assertSame('a4', $size['key']);
        $this->assertSame(210.0, $size['width_mm']);
        $this->assertSame(297.0, $size['height_mm']);
    }

    public function test_named_page_sizes_resolve(): void
    {
        $layout = $this->app->make(AgreementLetterheadLayout::class);

        $letter = $layout->pageSizeByKey('letter');
        $this->assertSame('letter', $letter['key']);
        $this->assertEqualsWithDelta(215.9, $letter['width_mm'], 0.05);
        $this->assertEqualsWithDelta(279.4, $letter['height_mm'], 0.05);

        $a5 = $layout->pageSizeByKey('a5');
        $this->assertSame('a5', $a5['key']);
        $this->assertSame(148.0, $a5['width_mm']);
        $this->assertSame(210.0, $a5['height_mm']);
    }

    public function test_unknown_page_size_falls_back_to_a4(): void
    {
        $layout = $this->app->make(AgreementLetterheadLayout::class);
        $size = $layout->pageSizeByKey('folio');

        $this->assertSame('a4', $size['key']);
        $this->assertContains('a4', $layout->allowedPageSizeKeys());
        $this->assertContains('letter', $layout->allowedPageSizeKeys());
    }

    public function test_content_padding_matches_full_page_margins_with_letterhead_overlay(): void
    {
        $layout = $this->app->make(AgreementLetterheadLayout::class);
        $margins = $layout->resolvedMarginsMm(null);
        $with = $layout->contentPaddingMm(null, true);
        $without = $layout->contentPaddingMm(null, false);

        $this->assertEquals($margins['top'], $with['top']);
        $this->assertEquals($margins['bottom'], $with['bottom']);
        $this->assertEquals($with, $without);
    }
}
