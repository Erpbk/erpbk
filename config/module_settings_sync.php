<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Main module table sync mapping
    |--------------------------------------------------------------------------
    | Maps UI table identifiers to module settings keys.
    | Used by shared UI components to apply module settings automatically.
    */
    'table_identifier_to_module' => [
        'bikes_table' => 'bike_list',
        'sims_table' => 'sims',
        'expense_vouchers_table' => 'expenses',
        'ledger_table' => 'ledger',
        'riders_table' => 'riders_list',
        'vouchers_table' => 'vouchers',
    ],
];

