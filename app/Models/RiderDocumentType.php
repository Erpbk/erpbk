<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderDocumentType extends BaseModel
{
    protected $table = 'rider_document_types';

    protected $fillable = [
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

    public function subtypes()
    {
        return $this->hasMany(RiderDocumentTypeSubtype::class, 'document_type_id')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function fields()
    {
        return $this->hasMany(RiderDocumentTypeField::class, 'document_type_id')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    /**
     * Scope: active types ordered by display_order.
     */
    public function scopeOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('id');
    }

    /**
     * Scope: all types for admin (including inactive), ordered.
     */
    public function scopeOrderedForAdmin($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * Build the expected-files structure (single + dual) for RidersController::files().
     */
    public static function expectedFilesStructure(): array
    {
        $types = self::orderedForAdmin()->get();
        $single = [];
        $dual = [];
        foreach ($types as $t) {
            if (\App\Support\RiderDocumentChecklist::isDualType($t)) {
                $dual[$t->key] = [
                    'front' => $t->front_label ?: 'Front',
                    'back' => $t->back_label ?: 'Back',
                ];
            } elseif ($t->label) {
                $single[$t->key] = $t->label;
            }
        }
        return ['single' => $single, 'dual' => $dual];
    }
}
