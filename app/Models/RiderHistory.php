<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderHistory extends Model
{
    public $table = 'rider_histories';

    public $fillable = [
        'rider_id',
        'branch_id',
        'customer_id',
        'fleet_supervisor',
        'bike_number',
        'history_status',
        'event_type',
        'title',
        'details',
        'meta',
        'effective_date',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'effective_date' => 'date',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Riders::class, 'rider_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class, 'customer_id', 'id');
    }
}
