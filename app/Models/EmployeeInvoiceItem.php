<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeInvoiceItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'item_id',
        'qty',
        'rate',
        'discount',
        'tax',
        'amount',
        'inv_id',
    ];
}

