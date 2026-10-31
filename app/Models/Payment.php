<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class Payment extends BaseModel
{
    use LogsActivity, BranchScope, SoftDeletes;

    public $table = 'payments';

    public $fillable = [
        'company_id',
        'branch_id',
        'reference',
        'resource_links',
        'bank_charges',
        'bank_charges_account',
        'bank_id',
        'amount_type',
        'payment_kind',
        'payee_account_id',
        'amount',
        'voucher_id',
        'date_of_invoice',
        'date_of_payment',
        'billing_month',
        'description',
        'internal_notes',
        'status',
        'created_by',
        'updated_by',
        'attachment',
        'deleted_by',
    ];

    protected $casts = [
        'resource_links' => 'array',
        'deleted_at' => 'datetime',
    ];

    public static array $rules = [
        'branch_id' => 'nullable',
        'bank_id' => 'required|numeric',
        'account_type' => 'nullable|string|max:255',
        'head_account_id' => 'nullable|string|max:255',
        'account_id' => 'nullable|string|max:255',
        'amount' => 'nullable|string|max:255',
        'date_of_invoice' => 'nullable|string|max:255',
        'date_of_payment' => 'nullable|string|max:255',
        'billing_month' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'status' => 'nullable|string|max:255',
        'created_by' => 'nullable|string|max:255',
        'updated_by' => 'nullable|string|max:255',
        'attachment' => 'nullable|string|max:255',
    ];

    public function voucher(){
        return $this->hasOne(Vouchers::class,'id','voucher_id');
    }

    public function bank(){
        return $this->belongsTo(Banks::class,'bank_id','id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id' , 'id');
    }

    public function getPayerAccountAttribute(){
        $bank = Banks::findOrFail($this->bank_id);
        $account = Accounts::find($bank->account_id);
        return $account->account_code .'-'. $account->name;
    }

    public function payeeAccount(){
        return $this->belongsTo(Accounts::class, 'payee_account_id', 'id');
    }

    public function getBranchNameAttribute()
  {
    $branch = $this->branch_id ? $this->branch->name .' ( '. $this->branch->code .' )' : 'All' ; 
    return $branch;
  }

    /**
     * Apply list filters / quick search for payment index pages.
     */
    public function scopeApplyListFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('date_of_payment')) {
            $query->whereDate('date_of_payment', $request->input('date_of_payment'));
        }

        if ($request->filled('billing_month')) {
            try {
                $rawMonth = (string) $request->input('billing_month');
                $billingMonth = Carbon::parse(strlen($rawMonth) === 7 ? $rawMonth . '-01' : $rawMonth);
                $query->whereYear('billing_month', $billingMonth->year)
                    ->whereMonth('billing_month', $billingMonth->month);
            } catch (\Throwable $e) {
                // ignore invalid month
            }
        }

        if ($request->filled('amount_type')) {
            $query->where('amount_type', $request->input('amount_type'));
        }

        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->input('bank_id'));
        }

        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->input('reference') . '%');
        }

        if ($request->filled('voucher_id')) {
            $voucherId = trim((string) $request->input('voucher_id'));
            $numericId = preg_replace('/\D+/', '', $voucherId);
            $query->where(function ($q) use ($voucherId, $numericId) {
                $q->where('voucher_id', 'like', '%' . $voucherId . '%');
                if ($numericId !== '') {
                    $q->orWhere('voucher_id', $numericId);
                }
            });
        }

        if ($request->filled('quick_search')) {
            $search = trim((string) $request->input('quick_search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('amount_type', 'like', "%{$search}%")
                    ->orWhere('voucher_id', 'like', "%{$search}%")
                    ->orWhereHas('payeeAccount', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%")
                            ->orWhere('account_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('bank.account', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%")
                            ->orWhere('account_code', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}
