<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Cheques extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cheques';
    protected $fillable = [
        'cheque_number',
        'bank_id',
        'amount',
        'payee_account',
        'payee_name',
        'payer_account',
        'payer_name',
        'reference',
        'attachment',
        'description',
        'issue_date',
        'cheque_date',
        'cleared_date',
        'returned_date',
        'stop_payment_date',
        'billing_month',
        'status',
        'return_reason',
        'type',
        'is_security',
        'voucher_id',
        'issued_by',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'issue_date' => 'datetime',
        'cheque_date' => 'datetime',
        'cleared_date' => 'datetime',
        'returned_date' => 'datetime',
        'stop_payment_date' => 'datetime',
        'billing_month'=> 'datetime',
        'amount' => 'decimal:2',
        'is_security' => 'boolean',
    ];
    protected $dates = ['deleted_at'];

    public static array $rules = [
        'cheque_number' => 'required|string',
        'bank_id' => 'required|exists:banks,id',
        'amount' => 'required|numeric|min:0',
        'payee_name' => 'nullable|string|max:255',
        'payer_name' => 'nullable|string|max:255',
        'issue_date' => 'required|date',
        'status' => 'required|in:Issued,Cleared,Returned,Stop Payment,Lost',
        'type' => 'required|in:payable,receiveable',
        'created_by' => 'required|exists:users,id',
    ];

    public function bank()
    {
        return $this->belongsTo(Banks::class, 'bank_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Vouchers::class, 'voucher_id');
    }

    public function payer()
    {
        return $this->belongsTo(Accounts::class, 'payer_account');
    }

    public function payee(){
        return $this->belongsTo(Accounts::class, 'payee_account');
    }

    public function Created_by(){
        return $this->belongsTo(User::class, 'created_by');
    }

    public function Updated_by(){
        return $this->belongsTo(User::class, 'updated_by');
    }
}