<?php

namespace App\Models;

class ModuleDocumentType extends BaseModel
{
    protected $table = 'module_document_types';

    protected $fillable = [
        'module_key',
        'company_id',
        'key',
        'label',
        'type',
        'front_label',
        'back_label',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
