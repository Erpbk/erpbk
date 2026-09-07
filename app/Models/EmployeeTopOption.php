<?php

namespace App\Models;

use App\Services\Permissions\TopBarOptionPermissionSync;

class EmployeeTopOption extends BaseModel
{
    protected $table = 'employee_top_options';

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
            TopBarOptionPermissionSync::syncOption('employees', $option);
        });

        static::deleted(function (self $option) {
            TopBarOptionPermissionSync::removeOption('employees', (int) $option->id);
        });
    }

    public function category()
    {
        return $this->belongsTo(EmployeeTopCategory::class, 'category_id', 'id');
    }
}
