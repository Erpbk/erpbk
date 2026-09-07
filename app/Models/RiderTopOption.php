<?php

namespace App\Models;

use App\Services\Permissions\RiderStatusPermissionSync;
use App\Services\Permissions\TopBarOptionPermissionSync;

class RiderTopOption extends BaseModel
{
    protected $table = 'rider_top_options';

    protected $fillable = [
        'category_id',
        'company_id',
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

    protected static function booted(): void
    {
        static::saved(function (self $option) {
            if (RiderStatusPermissionSync::isStatusOption($option)) {
                RiderStatusPermissionSync::syncOption($option);
                TopBarOptionPermissionSync::removeOption('riders', (int) $option->id);
            } else {
                TopBarOptionPermissionSync::syncOption('riders', $option);
                if ($option->wasChanged('category_id')) {
                    RiderStatusPermissionSync::removeOption((int) $option->id);
                }
            }
        });

        static::deleted(function (self $option) {
            RiderStatusPermissionSync::removeOption((int) $option->id);
            TopBarOptionPermissionSync::removeOption('riders', (int) $option->id);
        });
    }

    public function category()
    {
        return $this->belongsTo(RiderTopCategory::class, 'category_id', 'id');
    }
}
