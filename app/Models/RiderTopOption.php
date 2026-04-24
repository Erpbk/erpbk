<?php

namespace App\Models;

class RiderTopOption extends BaseModel
{
    protected $table = 'rider_top_options';

    protected $fillable = [
        'category_id',
        'name',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RiderTopCategory::class, 'category_id', 'id');
    }
}
