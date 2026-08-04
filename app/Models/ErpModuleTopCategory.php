<?php

namespace App\Models;

use App\Services\Permissions\TopBarPermissionSync;

class ErpModuleTopCategory extends BaseModel
{
    protected $table = 'erp_module_top_categories';

    protected $fillable = [
        'module_key',
        'company_id',
        'name',
        'db_column',
        'filter_type',
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
            TopBarPermissionSync::syncForCategory((string) ($category->module_key ?: 'module'), $category);
        });

        static::updated(function (self $category) {
            if ($category->wasChanged(['name', 'module_key'])) {
                TopBarPermissionSync::syncForCategory((string) ($category->module_key ?: 'module'), $category);
            }
        });

        static::deleted(function (self $category) {
            TopBarPermissionSync::removeForCategory((string) ($category->module_key ?: 'module'), (int) $category->id);
        });
    }

    public function options()
    {
        return $this->hasMany(ErpModuleTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
