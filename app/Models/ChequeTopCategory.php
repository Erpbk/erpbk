<?php

namespace App\Models;

use App\Services\Permissions\TopBarPermissionSync;

class ChequeTopCategory extends BaseModel
{
    protected $table = 'cheque_top_categories';

    protected $fillable = [
        'name',
        'filter_type',
        'cheque_column',
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
            TopBarPermissionSync::syncForCategory('cheques', $category);
        });

        static::updated(function (self $category) {
            if ($category->wasChanged('name')) {
                TopBarPermissionSync::syncForCategory('cheques', $category);
            }
        });

        static::deleted(function (self $category) {
            TopBarPermissionSync::removeForCategory('cheques', (int) $category->id);
        });
    }

    public function options()
    {
        return $this->hasMany(ChequeTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
