<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BikeDocumentType extends BaseModel
{
    protected $table = 'bike_document_types';

    protected $fillable = [
        'company_id',
        'key',
        'type',
        'label',
        'front_label',
        'back_label',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeOrderedForAdmin($query)
    {
        return $query->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id');
    }
}

