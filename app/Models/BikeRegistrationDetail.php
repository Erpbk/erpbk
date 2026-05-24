<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BikeRegistrationDetail extends BaseModel
{
    protected $table = 'bike_registration_details';

    protected $fillable = [
        'bike_registration_id',
        'description',
        'amount',
        'sort_order',
    ];

    public function bikeRegistration(): BelongsTo
    {
        return $this->belongsTo(BikeRegistration::class, 'bike_registration_id');
    }
}
