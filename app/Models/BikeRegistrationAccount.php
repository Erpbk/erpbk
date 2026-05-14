<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BikeRegistrationAccount extends BaseModel
{
    protected $table = 'bike_registration_accounts';

    protected $fillable = ['account_id', 'name', 'rider_id', 'bike_id', 'company_id', 'branch_id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bikes::class, 'bike_id', 'id');
    }

    public function bikeRegistrations(): HasMany
    {
        return $this->hasMany(BikeRegistration::class, 'bike_registration_account_id');
    }
}
