<?php

/**
 * Company-scoped global display preferences.
 * Add a key here + a form field to introduce a new preference without schema changes.
 */
return [
    'defaults' => [
        'font_family' => 'Public Sans',
        'date_format' => 'd-m-Y',
        'decimal_places' => '2',
        'thousand_separator' => ',',
        'decimal_separator' => '.',
        'currency_position' => 'before',
    ],

    /**
     * @var array<string, array{label: string, google?: string|null}>
     */
    'font_families' => [
        'Public Sans' => [
            'label' => 'Public Sans (default)',
            'google' => 'Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'system-ui' => [
            'label' => 'System UI',
            'google' => null,
        ],
        'Arial' => [
            'label' => 'Arial',
            'google' => null,
        ],
        'Georgia' => [
            'label' => 'Georgia',
            'google' => null,
        ],
        'Times New Roman' => [
            'label' => 'Times New Roman',
            'google' => null,
        ],
        'Open Sans' => [
            'label' => 'Open Sans',
            'google' => 'Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Roboto' => [
            'label' => 'Roboto',
            'google' => 'Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700',
        ],
        'Lato' => [
            'label' => 'Lato',
            'google' => 'Lato:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700',
        ],
        'Nunito Sans' => [
            'label' => 'Nunito Sans',
            'google' => 'Nunito+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Source Sans 3' => [
            'label' => 'Source Sans 3',
            'google' => 'Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Work Sans' => [
            'label' => 'Work Sans',
            'google' => 'Work+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Noto Sans' => [
            'label' => 'Noto Sans',
            'google' => 'Noto+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'IBM Plex Sans' => [
            'label' => 'IBM Plex Sans',
            'google' => 'IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'DM Sans' => [
            'label' => 'DM Sans',
            'google' => 'DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Mulish' => [
            'label' => 'Mulish',
            'google' => 'Mulish:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Rubik' => [
            'label' => 'Rubik',
            'google' => 'Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Poppins' => [
            'label' => 'Poppins',
            'google' => 'Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Montserrat' => [
            'label' => 'Montserrat',
            'google' => 'Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700',
        ],
        'Ubuntu' => [
            'label' => 'Ubuntu',
            'google' => 'Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700',
        ],
        'Merriweather' => [
            'label' => 'Merriweather',
            'google' => 'Merriweather:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700',
        ],
    ],

    'date_formats' => [
        'd-m-Y' => 'DD-MM-YYYY',
        'm-d-Y' => 'MM-DD-YYYY',
        'Y-m-d' => 'YYYY-MM-DD',
    ],

    'thousand_separators' => [
        ',' => 'Comma (,)',
        '.' => 'Dot (.)',
        'space' => 'Space',
        'none' => 'None',
    ],

    'decimal_separators' => [
        '.' => 'Dot (.)',
        ',' => 'Comma (,)',
    ],

    'currency_positions' => [
        'before' => 'Symbol before amount',
        'after' => 'Symbol after amount',
    ],

    'decimal_places_options' => [0, 1, 2, 3, 4],
];
