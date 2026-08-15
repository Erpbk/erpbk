<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class BikeRegistration extends BaseModel
{
    use LogsActivity, SoftDeletes;

    protected $table = 'bike_registrations';

    protected $with = ['vouchers'];

    protected $fillable = [
        'trans_date',
        'trans_code',
        'date',
        'rider_id',
        'registration_status',
        'detail',
        'branch_id',
        'company_id',
        'reference_number',
        'billing_month',
        'amount',
        'payment_status',
        'expiry_date',
        'deleted_by',
        'bike_registration_account_id',
        'pay_account',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public static array $rules = [
        'trans_date' => 'nullable',
        'trans_code' => 'nullable',
        'date' => 'required',
        'rider_id' => 'nullable',
        'billing_month' => 'required',
        'registration_status' => 'nullable|string|max:50',
        'detail' => 'nullable|string|max:500',
        'amount' => 'required|numeric',
        'payment_status' => 'nullable|numeric',
        'expiry_date' => 'nullable|date',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'branch_id' => 'nullable',
    ];

    public function rider()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }

    public function registrationAccount()
    {
        return $this->belongsTo(BikeRegistrationAccount::class, 'bike_registration_account_id');
    }

    public function account()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'trans_code', 'trans_code');
    }

    public function details()
    {
        return $this->hasMany(BikeRegistrationDetail::class, 'bike_registration_id');
    }

    public function vouchers()
    {
        return $this->hasMany(Vouchers::class, 'ref_id', 'id')
            ->where('voucher_type', 'BR');
    }

    public function getVoucherIdsAttribute()
    {
        if ($this->vouchers->isEmpty()) {
            if ($this->trans_code) {
                $fallback = Vouchers::where('trans_code', $this->trans_code)->where('voucher_type', 'BR')->get();

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
