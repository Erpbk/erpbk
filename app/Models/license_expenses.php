<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Models\Vouchers;

class license_expenses extends BaseModel
{
    use LogsActivity, SoftDeletes;

    protected $table = 'license_expenses';

    protected $with = ['vouchers'];

    protected $fillable = [
        'trans_date',
        'trans_code',
        'date',
        'rider_id',
        'license_status',
        'detail',
        'branch_id',
        'reference_number',
        'billing_month',
        'amount',
        'payment_status',
        'expiry_date',
        'deleted_by',
        'expense_account_id',
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
        'license_status' => 'nullable|string|max:50',
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
        // In current schema rider_id maps to accounts.id for visa ledgers.
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }
    public function expenseAccount()
    {
        return $this->belongsTo(ExpenseAccount::class, 'expense_account_id');
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
            ->where('voucher_type', 'LE')
            ->where(function ($q) {
                $q->whereNull('reason')->orWhere('reason', '!=', license_installment_plan::VOUCHER_REASON);
            });
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
