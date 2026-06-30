<?php

/**
 * Branded agreement PDF layout — A4 with header chrome and content safe zone.
 */
return [
    'page_width_mm' => 210,
    'page_height_mm' => 297,

    /** Space reserved for the fixed header chrome (logo, contact, rule). */
    'header_reserve_mm' => 40,

    /** Gap between header and content, as a fraction of page height (2%). */
    'content_top_gap_pct' => 0.02,

    /** Bottom printable margin as a fraction of page height (5%). */
    'content_bottom_pct' => 0.05,

    /** Horizontal content margins (mm). */
    'side_margins_mm' => [
        'left' => 12,
        'right' => 12,
    ],

    /**
     * Extra space below the header rule for DomPDF only (mm).
     * DomPDF renders absolute content slightly higher than browser preview.
     */
    'pdf_content_top_extra_mm' => 5,
];
