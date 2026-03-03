<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderFieldCategoryAssignment extends Model
{
    protected $table = 'rider_field_category_assignments';

    protected $fillable = [
        'field_key',
        'display_label',
        'category_id',
        'display_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RiderCategory::class, 'category_id', 'id');
    }
}
