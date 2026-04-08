<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class Payment extends Model
{
    use LogsActivity, BranchScope;

    public $table = 'payments';

    public $fillable = [
        'branch_id',
        'reference',
        'bank_charges',
        'bank_charges_account',
        'bank_id',
        'amount_type',
        'payee_account_id',
        'amount',
        'voucher_id',
        'date_of_invoice',
        'date_of_payment',
        'billing_month',
        'description',
        'status',
        'created_by',
        'updated_by',
        'attachment',
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
}
