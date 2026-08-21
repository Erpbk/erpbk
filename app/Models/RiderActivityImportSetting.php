<?php

namespace App\Models;

class RiderActivityImportSetting extends BaseModel
{
    public $table = 'rider_activity_import_settings';

    public $fillable = [
        'company_id',
        'customer_id',
        'import_type',
        'header_rows_to_skip',
        'column_mappings',
        'required_fields',
        'is_active',
    ];

    protected $casts = [
        'header_rows_to_skip' => 'integer',
        'column_mappings' => 'array',
        'required_fields' => 'array',
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id', 'id');
    }
}
