<?php

use App\Services\WhatsApp\Context\NotificationContextBuilder;
use App\Services\WhatsApp\Context\RiderActivityAlertContextBuilder;

/**
 * Meta WhatsApp template injections (source of truth for variable values).
 * When adding a new Meta template, add an injections[] entry here.
 *
 * inject keys must exist in inject_keys and be populated by the trigger's context builder.
 */
return [
    'injections' => [
        'notify_absent' => [
            'language' => 'en',
            'param_style' => 'named',
            'trigger_keys' => ['rider_activity:abs', 'rider_activity:labs'],
            'variables' => [
                ['component' => 'body', 'parameter_name' => 'name', 'inject' => 'rider_name'],
                ['component' => 'body', 'parameter_name' => 'id', 'inject' => 'rider_code'],
                ['component' => 'body', 'parameter_name' => 'reference_date', 'inject' => 'alert_date'],
                ['component' => 'body', 'parameter_name' => 'supervisor', 'inject' => 'fleet_supervisor'],
                ['component' => 'body', 'parameter_name' => 'company_name', 'inject' => 'company_name'],
            ],
        ],
        'notify_multiple_absents' => [
            'language' => 'en',
            'param_style' => 'named',
            'trigger_keys' => ['rider_activity:off'],
            'variables' => [
                ['component' => 'body', 'parameter_name' => 'name', 'inject' => 'rider_name'],
                ['component' => 'body', 'parameter_name' => 'id', 'inject' => 'rider_code'],
                ['component' => 'body', 'parameter_name' => 'absent_days', 'inject' => 'days_absent'],
                ['component' => 'body', 'parameter_name' => 'last_date', 'inject' => 'last_worked_date'],
                ['component' => 'body', 'parameter_name' => 'supervisor', 'inject' => 'fleet_supervisor'],
                ['component' => 'body', 'parameter_name' => 'company_name', 'inject' => 'company_name'],
            ],
        ],
        'hours_not_completed' => [
            'language' => 'en',
            'param_style' => 'named',
            'trigger_keys' => ['rider_activity:hrs', 'rider_activity:lhrs'],
            'variables' => [
                ['component' => 'body', 'parameter_name' => 'name', 'inject' => 'rider_name'],
                ['component' => 'body', 'parameter_name' => 'id', 'inject' => 'rider_code'],
                ['component' => 'body', 'parameter_name' => 'hours', 'inject' => 'hours'],
                ['component' => 'body', 'parameter_name' => 'reference_date', 'inject' => 'alert_date'],
                ['component' => 'body', 'parameter_name' => 'supervisor', 'inject' => 'fleet_supervisor'],
                ['component' => 'body', 'parameter_name' => 'company_name', 'inject' => 'company_name'],
            ],
        ],
        'orders_not_completed' => [
            'language' => 'en',
            'param_style' => 'named',
            'trigger_keys' => ['rider_activity:ord', 'rider_activity:lord'],
            'variables' => [
                ['component' => 'body', 'parameter_name' => 'name', 'inject' => 'rider_name'],
                ['component' => 'body', 'parameter_name' => 'id', 'inject' => 'rider_code'],
                ['component' => 'body', 'parameter_name' => 'orders', 'inject' => 'orders'],
                ['component' => 'body', 'parameter_name' => 'reference_date', 'inject' => 'alert_date'],
                ['component' => 'body', 'parameter_name' => 'supervisor', 'inject' => 'fleet_supervisor'],
                ['component' => 'body', 'parameter_name' => 'company_name', 'inject' => 'company_name'],
            ],
        ],
    ],

    /**
     * Whitelist of inject keys (labels for admin/docs).
     *
     * @var array<string, string>
     */
    'inject_keys' => [
        'rider_name' => 'Rider display name',
        'rider_code' => 'Rider ID / code (rider_id)',
        'rider_phone' => 'Rider WhatsApp phone',
        'days_absent' => 'Days not worked (filter / calculated)',
        'last_worked_date' => 'Last worked date (formatted)',
        'fleet_supervisor' => 'Fleet supervisor',
        'company_name' => 'Company name',
        'project_name' => 'Project / customer name',
        'hours' => 'Duty hours worked (filter date)',
        'orders' => 'Delivered orders (filter date)',
        'target' => 'Hours or orders target from filter',
        'alert_date' => 'Filter / reference date (formatted)',
        'notification_title' => 'Notification title',
        'notification_body' => 'Notification body',
        'cheque_number' => 'Cheque number',
        'amount' => 'Amount',
        'cheque_date' => 'Cheque date',
        'recipient_name' => 'Recipient name',
        'recipient_phone' => 'Recipient phone',
    ],

    /**
     * Context builders keyed by trigger (exact) or prefix wildcard (*).
     *
     * @var array<string, class-string>
     */
    'builders' => [
        'rider_activity:*' => RiderActivityAlertContextBuilder::class,
        'notification:*' => NotificationContextBuilder::class,
    ],

    /**
     * Human labels for trigger keys used in bindings UI.
     *
     * @var array<string, string>
     */
    'triggers' => [
        'rider_activity:abs' => 'Rider Activities — Absent',
        'rider_activity:off' => 'Rider Activities — Not working',
        'rider_activity:hrs' => 'Rider Activities — Duty hours not completed',
        'rider_activity:ord' => 'Rider Activities — Daily target not met',
        'rider_activity:labs' => 'Rider Live — Absent today',
        'rider_activity:lhrs' => 'Rider Live — Low hours',
        'rider_activity:lord' => 'Rider Live — Low orders',
        'notification:cheques.due_soon' => 'Notification — Cheque due soon',
        'notification:cheques.overdue' => 'Notification — Cheque overdue',
        'notification:delete_request_pending' => 'Notification — Delete request pending',
    ],
];
