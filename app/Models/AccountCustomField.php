<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountCustomField extends Model
{
    protected $table = 'account_custom_fields';

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
     * Data types supported for custom fields and their configuration options.
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

    /**
     * Get fixed (database) account field definitions - not deletable.
     */
    public static function fixedAccountFields(): array
    {
        return [
            ['key' => 'account_code', 'label' => 'Account Code', 'data_type' => 'string', 'is_mandatory' => false],
            ['key' => 'name', 'label' => 'Name', 'data_type' => 'string', 'is_mandatory' => true],
            ['key' => 'account_type', 'label' => 'Account Type', 'data_type' => 'string', 'is_mandatory' => true],
            ['key' => 'parent_id', 'label' => 'Parent Account', 'data_type' => 'relation', 'is_mandatory' => false],
            ['key' => 'ref_name', 'label' => 'Reference Name', 'data_type' => 'string', 'is_mandatory' => false],
            ['key' => 'ref_id', 'label' => 'Reference ID', 'data_type' => 'number', 'is_mandatory' => false],
            ['key' => 'status', 'label' => 'Status', 'data_type' => 'string', 'is_mandatory' => false],
            ['key' => 'notes', 'label' => 'Notes', 'data_type' => 'text', 'is_mandatory' => false],
            ['key' => 'opening_balance', 'label' => 'Opening Balance', 'data_type' => 'decimal', 'is_mandatory' => false],
            ['key' => 'is_locked', 'label' => 'Is Locked', 'data_type' => 'boolean', 'is_mandatory' => false],
        ];
    }
}
