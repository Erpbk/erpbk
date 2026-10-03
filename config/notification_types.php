<?php

return [
    'channels' => ['in_app', 'email'],

    'default_enabled_channels' => ['in_app', 'email'],

    'types' => [
        'cheques.due_soon' => [
            'label' => 'Cheque due soon',
            'module' => 'cheques',
            'trigger' => 'scheduled',
            'severity' => 'warning',
            'evaluator' => \App\Services\Notifications\Types\Cheques\ChequeDueSoonEvaluator::class,
            'default_config' => [
                'days_before' => [3],
                'statuses' => ['Issued'],
            ],
            'status_options' => ['Issued', 'Cleared', 'Returned', 'Stop Payment', 'Lost'],
            'default_recipient_config' => [
                'strategies' => [
                    'permission:cash_&_banks_cheques_view',
                    'record_owner',
                ],
            ],
            'default_channels' => ['in_app', 'email'],
        ],
        'cheques.overdue' => [
            'label' => 'Cheque overdue',
            'module' => 'cheques',
            'trigger' => 'condition',
            'severity' => 'critical',
            'evaluator' => \App\Services\Notifications\Types\Cheques\ChequeOverdueEvaluator::class,
            'default_config' => [
                'statuses' => ['Issued'],
            ],
            'status_options' => ['Issued', 'Cleared', 'Returned', 'Stop Payment', 'Lost'],
            'default_recipient_config' => [
                'strategies' => [
                    'permission:cash_&_banks_cheques_view',
                    'record_owner',
                ],
            ],
            'default_channels' => ['in_app', 'email'],
        ],
        'delete_request_pending' => [
            'label' => 'Delete request pending',
            'module' => 'settings',
            'trigger' => 'event',
            'severity' => 'warning',
            'evaluator' => null,
            'default_config' => [],
            'default_recipient_config' => [
                'strategies' => ['role:Administrator', 'role:Super Admin'],
            ],
            'default_channels' => ['in_app', 'email'],
        ],
    ],
];
