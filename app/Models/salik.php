<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class salik extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'transaction_id',
        'trip_date',
        'trip_time',
        'billing_month',
        'transaction_post_date',
        'toll_gate',
        'direction',
        'tag_number',
        'plate',
        'amount',
        'trans_date',
        'trans_code',
        'rider_id',
        'bike_id',
        'admin_charges',
        'salik_account_id',
        'attachments',
        'total_amount',
        'details',
        'status',
        'created_by',
        'updated_by',
    ];
}
