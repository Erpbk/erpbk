<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaRenewalCategory extends BaseModel
{
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

    public function visaStatuses(): HasMany
    {
        return $this->hasMany(VisaStatus::class, 'visa_renewal_category_id');
    }
}
