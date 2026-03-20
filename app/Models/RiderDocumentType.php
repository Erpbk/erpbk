<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderDocumentType extends Model
{
    protected $table = 'rider_document_types';

    protected $fillable = [
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
            if ($t->type === 'single' && $t->label) {
                $single[$t->key] = $t->label;
            }
            if ($t->type === 'dual' && $t->front_label && $t->back_label) {
                $dual[$t->key] = ['front' => $t->front_label, 'back' => $t->back_label];
            }
        }
        return ['single' => $single, 'dual' => $dual];
    }
}
