<?php

use App\Models\Employee;
use App\Models\Riders;

/**
 * ERP modules that support agreement contracts.
 * Keys must match agreement_categories.assigned_modules and menu module keys.
 */
return [
    'modules' => [
        'riders' => [
            'model' => Riders::class,
            'permissions' => ['agreement_view', 'agreement_generate', 'agreement_edit', 'rider_view'],
            'label_field' => 'name',
            'code_field' => 'rider_id',
            'email_field' => 'email',
        ],
        'employees' => [
            'model' => Employee::class,
            'permissions' => ['agreement_view', 'agreement_generate', 'agreement_edit', 'employees_view'],
            'label_field' => 'name',
            'code_field' => 'employee_id',
            'email_field' => 'company_email',
        ],
    ],
];
