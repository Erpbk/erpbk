<?php

/**
 * Letterhead PDF layout — A4 full-page artwork with automatic safe content zone.
 */
return [
    'page_width_mm' => 210,
    'page_height_mm' => 297,

    /** Default content safe-zone when no per-agreement overrides exist (mm). */
    'margins_mm' => [
        'top' => 48,
        'bottom' => 52,
        'left' => 18,
        'right' => 18,
    ],

    /**
     * When a letterhead is uploaded, estimate header/footer reserve from page height.
     * buffer_mm adds breathing room so text never touches artwork.
     */
    'auto_zones' => [
        'header_fraction' => 0.22,
        'footer_fraction' => 0.22,
        'buffer_mm' => 8,
        'ink_threshold' => 235,
        'row_ink_ratio' => 0.012,
        'col_ink_ratio' => 0.04,
    ],
];
