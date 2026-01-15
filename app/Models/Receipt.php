<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Receipt extends Model
{
    use LogsActivity;

    public $table = 'receipts';

    public $fillable = [
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

    protected $casts = [
        'payer_account_id' => 'array',
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

    public function receivedFrom(){
        
        $accounts = Accounts::whereIn('id', $this->payer_account_id)->get();
        $receivedFrom = "";
        foreach ($accounts as $index => $account){
            if($index==0)
                $receivedFrom .= $account->account_code . '-'. $account->name;
            else
                $receivedFrom .= "\n\n".$account->account_code . '-'. $account->name;
        }
        return $receivedFrom;
    }
}
