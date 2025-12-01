<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Models\Vouchers;

class visa_expenses extends Model
{
    use LogsActivity;

    protected $table = 'visa_expenses';

    protected $with = ['vouchers'];

    protected $fillable = [
        'trans_date',
        'trans_code',
        'date',
        'rider_id',
        'visa_status',
        'detail',
        'billing_month',
        'amount',
        'payment_status',
    ];
    public static array $rules = [
        'trans_date' => 'nullable',
        'trans_code' => 'nullable',
        'date' => 'required',
        'rider_id' => 'nullable',
        'billing_month' => 'required',
        'visa_status' => 'nullable|string|max:50',
        'detail' => 'nullable|string|max:500',
        'amount' => 'required|numeric',
        'payment_status' => 'nullable|numeric',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];
    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id', 'id');
    }
    public function account()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }
    function transactions()
    {
        return $this->hasMany(Transactions::class, 'trans_code', 'trans_code');
    }
    public function vouchers()
    {
        return $this->hasMany(Vouchers::class, 'ref_id', 'id')
            ->where('voucher_type', 'LV');
    }


    public function getVoucherIdsAttribute()
    {
        if ($this->vouchers->isEmpty()) {
            if ($this->trans_code) {
                $fallback = Vouchers::where('trans_code', $this->trans_code)->get();

                if ($fallback->isEmpty()) {
                    return '';
                }

                return $fallback->map(function ($voucher) {
                    return $voucher->formatted_id;
                })->implode(', ');
            }

            return '';
        }

        return $this->vouchers->map(function ($voucher) {
            return $voucher->formatted_id;
        })->implode(', ');
    }
}
