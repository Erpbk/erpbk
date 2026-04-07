<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class Receipt extends Model
{
    use LogsActivity, BranchScope;

    public $table = 'receipts';

    public $fillable = [
        'branch_id',
        'reference',
        'account_id',
        'bank_id',
        'payer_account_id',
        'amount',
        'amount_type',
        'voucher_id',
        'attachment',
        'date_of_receipt',
        'billing_month',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public static array $rules = [
        'reference' => 'nullable|string|max:255',
        'amount_type' => 'nullable|string|max:255',
        'account_id' => 'required|numeric|exists:accounts,id',
        'bank_id' => 'required|numeric|exists:banks,id',
        'payer_account_id'=> 'required|numeric',
        'amount' => 'required|numeric',
        'voucher_id'=> 'numeric',
        'date_of_receipt' => 'required|date',
        'billing_month' => 'required|date',
        'description' => 'nullable|string|max:255',
        'status' => 'nullable|numeric',
        'created_by' => 'nullable|string|max:255',
        'updated_by' => 'nullable|string|max:255',
    ];

    public function voucher(){
        return $this->hasOne(Vouchers::class,'id','voucher_id');
    }

    public function bank(){
        return $this->belongsTo(Banks::class,'bank_id','id');
    }

    public function payerAccount(){
        return $this->belongsTo(Accounts::class, 'payer_account_id', 'id');
    }

    public function payeeAccount(){
        return $this->belongsTo(Accounts::class, 'account_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id' , 'id');
    }

    public function getBranchNameAttribute()
    {
        $branch = $this->branch_id ? $this->branch->name .' ( '. $this->branch->code .' )' : 'All' ; 
        return $branch;
    }
}

