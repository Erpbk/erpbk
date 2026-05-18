<?php

namespace App\Models;

class EmployeeTopOption extends BaseModel
{
    protected $table = 'employee_top_options';

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

    public function category()
    {
        return $this->belongsTo(EmployeeTopCategory::class, 'category_id', 'id');
    }
}
