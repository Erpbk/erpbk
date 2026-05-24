<?php

namespace App\Models;

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

    public function options()
    {
        return $this->hasMany(ErpModuleTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
