<?php

/**
 * Shared agreement typography for the editor, browser print, and PDF download.
 */
return [
    'family' => 'Calibri',
    'fallbacks' => ['Segoe UI', 'Arial', 'sans-serif'],
    'size_pt' => 11.0,
    'line_height' => 1.5,
    'color' => '#1e293b',

    'sizes_pt' => [8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 36, 48],

    'headings_pt' => [
        'h1' => 16.0,
        'h2' => 14.0,
        'h3' => 12.0,
        'h4' => 11.0,
    ],

    /**
     * Families offered in the agreement editor ribbon.
     * Print and PDF both map unknown/Word fonts onto this list.
     *
     * @var list<string>
     */
    'families' => [
        'Calibri',
        'Cambria',
        'Arial',
        'Times New Roman',
        'Georgia',
        'Verdana',
        'Courier New',
        'Segoe UI',
        // Arabic (Naskh)
        'Noto Naskh Arabic',
        'Amiri',
        'Scheherazade New',
        // Urdu (Nastaliq / Arabic-script)
        'Noto Nastaliq Urdu',
        'Lateef',
        'Harmattan',
    ],

    /**
     * Word/CSS aliases → canonical family name.
     *
     * @var array<string, string>
     */
    'aliases' => [
        'calibri light' => 'Calibri',
        'calibri-light' => 'Calibri',
        'carlito' => 'Calibri',
        'segoeui' => 'Segoe UI',
        'segoe ui' => 'Segoe UI',
        'helvetica' => 'Arial',
        'helvetica neue' => 'Arial',
        'liberation sans' => 'Arial',
        'times' => 'Times New Roman',
        'times new roman' => 'Times New Roman',
        'timesnewroman' => 'Times New Roman',
        'liberation serif' => 'Times New Roman',
        'courier' => 'Courier New',
        'courier new' => 'Courier New',
        'liberation mono' => 'Courier New',
        'sans-serif' => 'Calibri',
        'serif' => 'Times New Roman',
        'monospace' => 'Courier New',
            'aptos' => 'Calibri',
        'arial unicode ms' => 'Arial',
        'google sans' => 'Calibri',
        'noto naskh arabic' => 'Noto Naskh Arabic',
        'notonaskharabic' => 'Noto Naskh Arabic',
        'amiri' => 'Amiri',
        'scheherazade' => 'Scheherazade New',
        'scheherazade new' => 'Scheherazade New',
        'scheherazadenew' => 'Scheherazade New',
        'noto nastaliq urdu' => 'Noto Nastaliq Urdu',
        'notonastaliqurdu' => 'Noto Nastaliq Urdu',
        'nastaliq' => 'Noto Nastaliq Urdu',
        'lateef' => 'Lateef',
        'harmattan' => 'Harmattan',
    ],
];
