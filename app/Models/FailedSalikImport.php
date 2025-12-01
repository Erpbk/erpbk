<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class FailedSalikImport extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'transaction_id',
        'trip_date',
        'plate_number',
        'amount',
        'reason',
        'details',
        'row_number',
        'raw_data',
        'import_batch_id'
    ];

    protected $casts = [
        'trip_date' => 'date',
        'amount' => 'decimal:2',
        'raw_data' => 'array'
    ];
}
