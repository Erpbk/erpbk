<?php

namespace Tests\Unit;

use App\Services\Agreements\AgreementLetterheadPaginator;
use PHPUnit\Framework\TestCase;

class AgreementLetterheadPaginatorTest extends TestCase
{
    public function test_short_body_stays_on_one_page(): void
    {
        $pages = (new AgreementLetterheadPaginator())->paginate(
            '<h2>Rider Contract</h2><p>This agreement is between the company and the rider.</p>',
            238.0
        );

        $this->assertCount(1, $pages);
        $this->assertStringContainsString('Rider Contract', $pages[0]);
    }

    public function test_long_body_splits_before_overflowing_a4(): void
    {
        $paragraphs = '';
        for ($i = 1; $i <= 80; $i++) {
            $paragraphs .= '<p>Clause '.$i.'. The rider agrees to follow company policies, traffic laws, and safety rules during all delivery assignments in the United Arab Emirates.</p>';
        }

        $pages = (new AgreementLetterheadPaginator())->paginate($paragraphs, 238.0);

        $this->assertGreaterThan(1, count($pages));
        $this->assertNotSame('', trim(strip_tags($pages[0])));
        $this->assertNotSame('', trim(strip_tags($pages[array_key_last($pages)])));
    }
}
