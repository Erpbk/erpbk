<?php

namespace App\Models;

class ExcelImportMapping extends BaseModel
{
    public $table = 'excel_import_mappings';

    public $fillable = [
        'company_id',
        'module_key',
        'import_type',
        'scope_type',
        'scope_id',
        'header_rows_to_skip',
        'column_mappings',
        'dynamic_mappings',
        'required_fields',
        'options',
        'is_active',
    ];

    protected $casts = [
        'header_rows_to_skip' => 'integer',
        'column_mappings' => 'array',
        'dynamic_mappings' => 'array',
        'required_fields' => 'array',
        'options' => 'array',
        'is_active' => 'boolean',
        'scope_id' => 'integer',
    ];
}
