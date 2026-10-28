<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisaRenewalCategory extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'display_order',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function visaExpenses(): HasMany
    {
        return $this->hasMany(visa_expenses::class, 'renewal_category_id');
    }

    public function visaStatuses(): BelongsToMany
    {
        return $this->belongsToMany(
            VisaStatus::class,
            'visa_renewal_category_visa_status',
            'visa_renewal_category_id',
            'visa_status_id'
        )->withPivot('display_order')->withTimestamps()
            ->orderByPivot('display_order')
            ->orderBy('visa_statuses.name');
    }
}
