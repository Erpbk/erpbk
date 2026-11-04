<?php

namespace App\Models;

class RiderDocumentTypeSubtype extends BaseModel
{
    protected $table = 'rider_document_type_subtypes';

    protected $fillable = [
        'company_id',
        'document_type_id',
        'key',
        'label',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documentType()
    {
        return $this->belongsTo(RiderDocumentType::class, 'document_type_id');
    }

    public function fields()
    {
        return $this->hasMany(RiderDocumentTypeField::class, 'subtype_id')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function scopeOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('id');
    }

    public function scopeOrderedForAdmin($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
