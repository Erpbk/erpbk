<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    public $table = 'payments';

    public $fillable = [
        'reference',
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

    protected $casts = [
        'payee_account_id' => 'array',
    ];

    public static array $rules = [
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

    public function payedTo(){
        
        $accounts = Accounts::whereIn('id', $this->payee_account_id)->get();
        $payedTo = "";
        foreach ($accounts as $index => $account){
            if($index==0)
                $payedTo .= $account->account_code . '-'. $account->name;
            else
                $payedTo .= "\n\n".$account->account_code . '-'. $account->name;
        }
        return $payedTo;
    }
}
