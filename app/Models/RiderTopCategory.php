<?php

namespace App\Models;

use App\Models\RiderTopOption;

class RiderTopCategory extends BaseModel
{
    protected $table = 'rider_top_categories';

    protected $fillable = [
        'name',
        'filter_type',
        'rider_column',
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

    public function options()
    {
        return $this->hasMany(RiderTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
