<?php

namespace App\Models;

class ChequeTopCategory extends BaseModel
{
    protected $table = 'cheque_top_categories';

    protected $fillable = [
        'name',
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

    public function options()
    {
        return $this->hasMany(ChequeTopOption::class, 'category_id', 'id')
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
