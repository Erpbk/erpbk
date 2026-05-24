<?php

namespace App\Models;

class ChequeTopOption extends BaseModel
{
    protected $table = 'cheque_top_options';

    protected $fillable = [
        'category_id',
        'name',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ChequeTopCategory::class, 'category_id', 'id');
    }
}
