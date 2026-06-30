<?php

/**
 * Branded agreement PDF layout — A4 with header chrome and content safe zone.
 */
return [
    'page_width_mm' => 210,
    'page_height_mm' => 297,

    /** Space reserved for the fixed header chrome (logo, contact, rule). */
    'header_reserve_mm' => 43,

    /**
     * Top margin above the letterhead block (logo/contact area) on each page (mm).
     */
    'header_top_margin_mm' => 8,

    /**
     * Total header block height in the page flow (mm).
     * Matches .page-header padding-top + logo + rule in letterhead.blade.php.
     */
    'header_chrome_height_mm' => 33,

    /** Gap between header and content, as a fraction of page height. */
    'content_top_gap_pct' => 0.02,

    /** Space reserved above the footer decoration (mm). */
    'footer_reserve_mm' => 10,

    /** @deprecated Use footer_reserve_mm. Kept for display labels only. */
    'content_bottom_pct' => 0.034,

    /** Horizontal content margins (mm). */
    'side_margins_mm' => [
        'left' => 12,
        'right' => 12,
    ],

    /** @deprecated Not used for layout; vertical safe zone matches letterhead mode. */
    'plain_margins_mm' => [
        'top' => 15,
        'bottom' => 15,
        'left' => 12,
        'right' => 12,
    ],

    /**
     * Extra space below the header rule for DomPDF only (mm).
     * DomPDF renders absolute content slightly higher than browser preview.
     */
    'pdf_content_top_extra_mm' => 5,
];
