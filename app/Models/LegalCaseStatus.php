<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalCaseStatus extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'legal_case_statuses';

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'is_active',
        'is_required',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
    ];

    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }
}
