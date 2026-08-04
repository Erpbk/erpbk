<?php

namespace App\Models;

use App\Services\Permissions\TopBarPermissionSync;

class BikeTopCategory extends BaseModel
{
    protected $table = 'bike_top_categories';

    protected $fillable = [
        'name',
        'filter_type',
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

    protected static function booted(): void
    {
        static::created(function (self $category) {
            TopBarPermissionSync::syncForCategory('bike_list', $category);
        });

        static::updated(function (self $category) {
            if ($category->wasChanged('name')) {
                TopBarPermissionSync::syncForCategory('bike_list', $category);
            }
        });

        static::deleted(function (self $category) {
            TopBarPermissionSync::removeForCategory('bike_list', (int) $category->id);
        });
    }

    public function options()
    {
        return $this->hasMany(BikeTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
