<?php

namespace App\Models;

class BikeTopCategory extends BaseModel
{
    protected $table = 'bike_top_categories';

    protected $fillable = [
        'name',
        'bike_column',
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
        return $this->hasMany(BikeTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
