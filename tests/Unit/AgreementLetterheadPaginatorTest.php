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

    public function test_editor_page_break_starts_a_new_sheet(): void
    {
        $first = '';
        for ($i = 1; $i <= 40; $i++) {
            $first .= '<p>Page one clause '.$i.'. The rider agrees to follow company policies during delivery assignments in the United Arab Emirates.</p>';
        }

        $pages = (new AgreementLetterheadPaginator())->paginate(
            $first.'<p data-agreement-page-break="1" class="agreement-page-break">&nbsp;</p><p>Forced second page starts here.</p>',
            238.0
        );

        $this->assertGreaterThanOrEqual(2, count($pages));
        $this->assertStringContainsString('Forced second page starts here', implode('', $pages));
        $this->assertStringNotContainsString('Forced second page starts here', $pages[0]);
    }

    public function test_editor_page_break_does_not_stop_a4_overflow_splits(): void
    {
        $rest = '';
        for ($i = 1; $i <= 80; $i++) {
            $rest .= '<p>Clause '.$i.'. The rider agrees to follow company policies, traffic laws, and safety rules during all delivery assignments in the United Arab Emirates.</p>';
        }

        $pages = (new AgreementLetterheadPaginator())->paginate(
            '<p>Cover heading.</p><p data-agreement-page-break="1" class="agreement-page-break">&nbsp;</p>'.$rest,
            238.0
        );

        $this->assertGreaterThan(2, count($pages));
        $this->assertStringContainsString('Cover heading', $pages[0]);
        $this->assertStringNotContainsString('Clause 80', $pages[0]);
        $this->assertStringContainsString('Clause 80', $pages[array_key_last($pages)]);
    }

    public function test_page_break_moves_declaration_even_when_page_one_has_space(): void
    {
        $pages = (new AgreementLetterheadPaginator())->paginate(
            '<p>First block.</p><p data-agreement-page-break="1" class="agreement-page-break">&nbsp;</p><h2>Declaration</h2><p>Declaration starts here on the next sheet.</p>',
            238.0
        );

        $this->assertCount(2, $pages);
        $this->assertStringContainsString('First block', $pages[0]);
        $this->assertStringNotContainsString('Declaration', $pages[0]);
        $this->assertStringContainsString('Declaration', $pages[1]);
        $this->assertStringContainsString('Declaration starts here', $pages[1]);
    }

    public function test_embedded_image_is_kept_in_paginated_html(): void
    {
        $img = '<p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==" width="400" height="300" alt="scan"></p>';
        $pages = (new AgreementLetterheadPaginator())->paginate($img.'<p>Caption after photo.</p>', 238.0);

        $this->assertStringContainsString('<img', $pages[0]);
        $this->assertStringContainsString('Caption after photo', implode('', $pages));
    }

    public function test_image_only_body_is_not_dropped(): void
    {
        $img = '<p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==" alt="scan"></p>';
        $pages = (new AgreementLetterheadPaginator())->paginate($img, 238.0);

        $this->assertCount(1, $pages);
        $this->assertStringContainsString('<img', $pages[0]);
    }

    public function test_trailing_image_does_not_create_an_empty_extra_page(): void
    {
        $img = '<p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==" width="149" height="135" alt="logo"></p>';
        $html = '<p>Last clause on this sheet.</p>';
        for ($i = 0; $i < 8; $i++) {
            $html .= '<p class="fw-bold" style="text-align: right;"><br></p>';
        }
        $html .= $img;

        $pages = (new AgreementLetterheadPaginator())->paginate($html, 238.0);

        $this->assertCount(1, $pages);
        $this->assertStringContainsString('<img', $pages[0]);
        $this->assertStringContainsString('Last clause on this sheet', $pages[0]);
    }

    public function test_leading_editor_page_break_does_not_leave_a_blank_first_page(): void
    {
        $pages = (new AgreementLetterheadPaginator())->paginate(
            '<p data-agreement-page-break="1" class="agreement-page-break">&nbsp;</p><p>Hello from the first sheet.</p>',
            238.0
        );

        $this->assertCount(1, $pages);
        $this->assertStringContainsString('Hello from the first sheet', $pages[0]);
    }

    public function test_stacked_word_page_gaps_are_ignored(): void
    {
        $pages = (new AgreementLetterheadPaginator())->paginate(
            '<p>Cover.</p><div class="word-page-gap" data-word-page-gap="1">&nbsp;</div><div class="word-page-gap" data-word-page-gap="1">&nbsp;</div><p>Declaration starts here.</p>',
            238.0
        );

        $this->assertCount(1, $pages);
        $this->assertStringContainsString('Cover', $pages[0]);
        $this->assertStringContainsString('Declaration starts here', $pages[0]);
    }

    public function test_clause_number_stays_with_its_title(): void
    {
        $clauses = '';
        for ($i = 1; $i <= 9; $i++) {
            $clauses .= '<div class="clause-content"><div class="clause-title"><span class="clause-number">'.$i.'.</span><strong>Clause title '.$i.'</strong></div>'
                .'<p>The rider agrees to follow company policies, traffic laws, and safety rules during all delivery assignments in the United Arab Emirates.</p></div>';
        }

        $pages = (new AgreementLetterheadPaginator())->paginate('<h2>Clauses</h2>'.$clauses, 238.0);
        $all = implode(' ', $pages);

        foreach ($pages as $page) {
            $text = trim(preg_replace('/\s+/u', ' ', strip_tags($page)) ?? '');
            $this->assertDoesNotMatchRegularExpression('/\d+\.\s*$/', $text);
        }
        $this->assertStringContainsString('Clause title 1', $all);
        $this->assertStringContainsString('Clause title 9', $all);
    }

    public function test_underlined_heading_with_br_stays_with_following_block(): void
    {
        $filler = '';
        for ($i = 1; $i <= 35; $i++) {
            $filler .= '<p>Filler clause '.$i.'. The rider agrees to follow company policies during delivery assignments in the United Arab Emirates.</p>';
        }

        $pages = (new AgreementLetterheadPaginator())->paginate(
            $filler.'<p><strong style="font-size: 10pt;"><span style="text-decoration: underline;">Contract Highlights</span></strong><br><span style="font-size: 10pt;"></span></p>'
            .'<p>Supply cities and contract period details follow the heading on the same sheet.</p>',
            238.0
        );

        foreach ($pages as $page) {
            if (str_contains($page, 'Contract Highlights')) {
                $this->assertStringContainsString('Supply cities', $page);
            }
        }
    }

    public function test_plain_title_line_stays_with_following_table(): void
    {
        $filler = '';
        for ($i = 1; $i <= 35; $i++) {
            $filler .= '<p>Filler clause '.$i.'. The rider agrees to follow company policies during delivery assignments in the United Arab Emirates.</p>';
        }

        $pages = (new AgreementLetterheadPaginator())->paginate(
            $filler.'<p>Contract Highlights</p>'
            .'<table border="1"><tr><td>Supply Cities</td><td>Dubai</td></tr><tr><td>Period</td><td>12 Months</td></tr></table>',
            238.0
        );

        foreach ($pages as $page) {
            if (str_contains($page, 'Contract Highlights')) {
                $this->assertStringContainsString('Supply Cities', $page);
            }
        }
    }

    public function test_pull_leading_blocks_does_not_orphan_title_from_table(): void
    {
        $page1 = '';
        for ($i = 1; $i <= 20; $i++) {
            $page1 .= '<p>Page one clause '.$i.'. The rider agrees to follow company policies during delivery assignments in the United Arab Emirates.</p>';
        }
        $page2 = '<p><strong><span style="text-decoration: underline;">Contract Highlights</span></strong><br></p>'
            .'<table border="1"><tr><td>Supply Cities</td><td>Dubai</td></tr></table>';

        // Mimic a packed result where the title+table already share page 2, then
        // ensure rebalance/pull cannot yank only the title back to page 1.
        $pages = (new AgreementLetterheadPaginator())->paginate($page1.$page2, 246.0);

        foreach ($pages as $page) {
            if (str_contains($page, 'Contract Highlights')) {
                $this->assertStringContainsString('Supply Cities', $page);
            }
        }
    }

    public function test_empty_paragraphs_are_kept_for_spacing(): void
    {
        $html = '<p>Title</p><p>&nbsp;</p><p>After a blank line.</p>';
        $pages = (new AgreementLetterheadPaginator())->paginate($html, 238.0);

        $this->assertCount(1, $pages);
        $this->assertMatchesRegularExpression('/<p>(&nbsp;|\x{00A0}|\s)*<\/p>/u', $pages[0]);
        $this->assertStringContainsString('After a blank line', $pages[0]);
    }

    public function test_empty_div_spacers_can_push_content_to_the_next_page(): void
    {
        $html = '<p>Cover.</p>';
        for ($i = 0; $i < 50; $i++) {
            $html .= '<div class="col-4" style="text-align: left;">&nbsp;</div>';
        }
        $html .= '<h1>Declaration</h1><p>Body after spacers.</p>';

        $pages = (new AgreementLetterheadPaginator())->paginate($html, 238.0);

        $this->assertGreaterThan(1, count($pages));
        $this->assertStringContainsString('Cover', $pages[0]);
        $this->assertStringNotContainsString('Declaration', $pages[0]);
        $this->assertStringContainsString('Declaration', $pages[array_key_last($pages)]);
    }
}
