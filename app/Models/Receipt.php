<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Receipt extends Model
{
    use LogsActivity;

    public $table = 'receipts';

    public $fillable = [
        'transaction_number',
        'account_type',
        'head_account_id',
        'account_id',
        'bank_id',
        'amount',
        'date_of_receipt',
        'billing_month',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_number' => 'string',
        'account_type' => 'string',
        'head_account_id' => 'string',
        'account_id' => 'string',
        'bank_id' => 'string',
        'amount' => 'decimal:2',
        'date_of_receipt' => 'string',
        'billing_month' => 'string',
        'description' => 'string',
        'status' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public static array $rules = [
        'transaction_number' => 'nullable|string|max:255|unique:receipts,transaction_number',
        'account_type' => 'nullable|string|max:255',
        'head_account_id' => 'nullable|string|max:255',
        'account_id' => 'nullable|string|max:255',
        'bank_id' => 'nullable|string|max:255',
        'amount' => 'required|numeric',
        'date_of_receipt' => 'nullable|string|max:255',
        'billing_month' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:255',
        'status' => 'nullable|string|max:255',
        'created_by' => 'nullable|string|max:255',
        'updated_by' => 'nullable|string|max:255',
    ];
}
