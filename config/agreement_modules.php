<?php

/**
 * ERP modules that can show the Agreements submenu when agreements are assigned.
 * Keys must match agreement_categories.assigned_modules and menu module keys.
 */
return [
    'modules' => [
        'riders' => [
            'permissions' => ['agreement_view', 'agreement_generate', 'agreement_edit', 'rider_view'],
        ],
        'employees' => [
            'permissions' => ['agreement_view', 'agreement_generate', 'agreement_edit', 'employees_view'],
        ],
    ],
];
