<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherCustomField extends Model
{
    protected $table = 'voucher_custom_fields';

    protected $fillable = [
        'label',
        'help_text',
        'data_privacy',
        'prevent_duplicate_values',
        'default_value',
        'input_format',
        'data_type',
        'is_mandatory',
        'config',
        'display_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'prevent_duplicate_values' => 'boolean',
        'data_privacy' => 'array',
        'config' => 'array',
    ];

    /**
     * Data types supported for voucher custom fields (same as account custom fields).
     */
    public static function dataTypes(): array
    {
        return [
            'text' => [
                'label' => 'Text',
                'config' => [
                    ['key' => 'max_length', 'label' => 'Max length', 'type' => 'number', 'default' => 255],
                    ['key' => 'placeholder', 'label' => 'Placeholder', 'type' => 'text'],
                ],
            ],
            'textarea' => [
                'label' => 'Textarea',
                'config' => [
                    ['key' => 'max_length', 'label' => 'Max length', 'type' => 'number', 'default' => 1000],
                    ['key' => 'rows', 'label' => 'Rows', 'type' => 'number', 'default' => 4],
                ],
            ],
            'number' => [
                'label' => 'Number',
                'config' => [
                    ['key' => 'min', 'label' => 'Minimum', 'type' => 'number'],
                    ['key' => 'max', 'label' => 'Maximum', 'type' => 'number'],
                    ['key' => 'step', 'label' => 'Step', 'type' => 'number', 'default' => 1],
                ],
            ],
            'decimal' => [
                'label' => 'Decimal',
                'config' => [
                    ['key' => 'min', 'label' => 'Minimum', 'type' => 'number'],
                    ['key' => 'max', 'label' => 'Maximum', 'type' => 'number'],
                    ['key' => 'decimals', 'label' => 'Decimal places', 'type' => 'number', 'default' => 2],
                ],
            ],
            'date' => [
                'label' => 'Date',
                'config' => [
                    ['key' => 'format', 'label' => 'Display format', 'type' => 'text', 'default' => 'Y-m-d'],
                ],
            ],
            'datetime' => [
                'label' => 'Date & Time',
                'config' => [
                    ['key' => 'format', 'label' => 'Display format', 'type' => 'text', 'default' => 'Y-m-d H:i'],
                ],
            ],
            'dropdown' => [
                'label' => 'Dropdown',
                'config' => [
                    ['key' => 'options', 'label' => 'Options (one per line)', 'type' => 'textarea', 'placeholder' => 'Option 1\nOption 2'],
                ],
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'config' => [
                    ['key' => 'default_checked', 'label' => 'Default checked', 'type' => 'checkbox', 'default' => false],
                ],
            ],
            'email' => [
                'label' => 'Email',
                'config' => [
                    ['key' => 'placeholder', 'label' => 'Placeholder', 'type' => 'text'],
                ],
            ],
            'url' => [
                'label' => 'URL',
                'config' => [
                    ['key' => 'placeholder', 'label' => 'Placeholder', 'type' => 'text'],
                ],
            ],
        ];
    }
}
