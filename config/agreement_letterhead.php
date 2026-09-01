<?php

/**
 * Branded agreement PDF layout — A4 with header chrome and content safe zone.
 */
return [
    'default_page_size' => 'a4',

    /**
     * Named paper sizes (portrait). Keys are stored on the agreement category.
     *
     * @var array<string, array{label: string, width_mm: float, height_mm: float, dompdf: string}>
     */
    'page_sizes' => [
        'a3' => ['label' => 'A3', 'width_mm' => 297.0, 'height_mm' => 420.0, 'dompdf' => 'a3'],
        'a4' => ['label' => 'A4', 'width_mm' => 210.0, 'height_mm' => 297.0, 'dompdf' => 'a4'],
        'a5' => ['label' => 'A5', 'width_mm' => 148.0, 'height_mm' => 210.0, 'dompdf' => 'a5'],
        'letter' => ['label' => 'Letter', 'width_mm' => 215.9, 'height_mm' => 279.4, 'dompdf' => 'letter'],
        'legal' => ['label' => 'Legal', 'width_mm' => 215.9, 'height_mm' => 355.6, 'dompdf' => 'legal'],
        'tabloid' => ['label' => 'Tabloid', 'width_mm' => 279.4, 'height_mm' => 431.8, 'dompdf' => 'tabloid'],
    ],

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
    'footer_reserve_mm' => 8,

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
];
