<?php

namespace App\Models;

use App\Models\RiderTopOption;
use App\Services\Permissions\RiderStatusPermissionSync;
use App\Services\Permissions\TopBarPermissionSync;

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

    protected static function booted(): void
    {
        static::created(function (self $category) {
            TopBarPermissionSync::syncForCategory('riders', $category);
        });

        static::updated(function (self $category) {
            if ($category->wasChanged('name')) {
                TopBarPermissionSync::syncForCategory('riders', $category);
            }
        });

        static::deleting(function (self $category) {
            if (trim((string) $category->rider_column) === 'rider_status') {
                RiderStatusPermissionSync::removeAllForCategory((int) $category->id);
            }
        });

        static::deleted(function (self $category) {
            TopBarPermissionSync::removeForCategory('riders', (int) $category->id);
        });
    }

    public function options()
    {
        return $this->hasMany(RiderTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
