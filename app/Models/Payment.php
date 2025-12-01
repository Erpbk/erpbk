<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    public $table = 'payments';

    public $fillable = [
        'bank_id',
        'account_type',
        'head_account_id',
        'account_id',
        'amount',
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
        'bank_id' => 'string',
        'account_type' => 'string',
        'head_account_id' => 'string',
        'account_id' => 'string',
        'amount' => 'decimal:2',
        'date_of_invoice' => 'string',
        'date_of_payment' => 'string',
        'billing_month' => 'string',
        'voucher_no' => 'string',
        'voucher_type' => 'string',
        'description' => 'string',
        'status' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
        'attachment' => 'string',
    ];

    public static array $rules = [
        'bank_id' => 'nullable|string|max:255',
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
}
