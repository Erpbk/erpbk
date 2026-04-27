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
        'show_in_top_bar',
        'show_in_view_cards',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_top_bar' => 'boolean',
        'show_in_view_cards' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RiderTopCategory::class, 'category_id', 'id');
    }
}
