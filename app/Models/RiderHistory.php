<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderHistory extends Model
{
    public $table = 'rider_histories';

    public $fillable = [
        'rider_id',
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
}
