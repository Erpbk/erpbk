<?php

namespace App\Models;

class RiderDocumentTypeField extends BaseModel
{
    protected $table = 'rider_document_type_fields';

    protected $fillable = [
        'company_id',
        'document_type_id',
        'subtype_id',
        'key',
        'label',
        'input_type',
        'is_required',
        'display_order',
        'rider_column',
        'options',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'options' => 'array',
    ];

    public const INPUT_TYPES = ['text', 'textarea', 'number', 'date', 'select'];

    public function documentType()
    {
        return $this->belongsTo(RiderDocumentType::class, 'document_type_id');
    }

    public function subtype()
    {
        return $this->belongsTo(RiderDocumentTypeSubtype::class, 'subtype_id');
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
