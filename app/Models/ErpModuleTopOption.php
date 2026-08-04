<?php

namespace App\Models;

use App\Services\Permissions\TopBarOptionPermissionSync;

class ErpModuleTopOption extends BaseModel
{
    protected $table = 'erp_module_top_options';

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

    protected static function booted(): void
    {
        static::saved(function (self $option) {
            if (! $option->relationLoaded('category')) {
                $option->load('category');
            }
            $moduleKey = (string) ($option->category?->module_key ?: 'module');
            TopBarOptionPermissionSync::syncOption($moduleKey, $option);
        });

        static::deleted(function (self $option) {
            if (! $option->relationLoaded('category')) {
                $option->load('category');
            }
            $moduleKey = (string) ($option->category?->module_key ?: 'module');
            TopBarOptionPermissionSync::removeOption($moduleKey, (int) $option->id);
        });
    }

    public function category()
    {
        return $this->belongsTo(ErpModuleTopCategory::class, 'category_id', 'id');
    }
}
