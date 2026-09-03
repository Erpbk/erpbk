<?php

namespace App\Models;

use App\Services\Permissions\TopBarOptionPermissionSync;

class ChequeTopOption extends BaseModel
{
    protected $table = 'cheque_top_options';

    protected $fillable = [
        'category_id',
        'company_id',
        'name',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $option) {
            TopBarOptionPermissionSync::syncOption('cheques', $option);
        });

        static::deleted(function (self $option) {
            TopBarOptionPermissionSync::removeOption('cheques', (int) $option->id);
        });
    }

    public function category()
    {
        return $this->belongsTo(ChequeTopCategory::class, 'category_id', 'id');
    }
}
