<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class cod extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'rider_id',
        'transaction_date',
        'transaction_time',
        'billing_month',
        'amount',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'billing_month' => 'date',
        'amount' => 'decimal:2',
    ];

    public static array $rules = [
        'rider_id' => 'required|numeric',
        'transaction_date' => 'required|date',
        'transaction_time' => 'nullable|string|max:255',
        'billing_month' => 'nullable|date',
        'amount' => 'required|numeric|min:0',
        'description' => 'nullable|string',
        'status' => 'nullable|string|max:255',
    ];

    public function rider()
    {
        return $this->belongsTo(Riders::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'reference_id', 'id')
            ->where('reference_type', 'COD');
    }

    public function vouchers()
    {
        return $this->hasMany(Vouchers::class, 'ref_id', 'id')
            ->where('voucher_type', 'COD');
    }
}
