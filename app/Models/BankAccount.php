<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\TracksUserAttribution;

class BankAccount extends BaseModel
{
    use SoftDeletes, TracksUserAttribution;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'name',
        'account_number',
        'bank_name',
        'branch',
        'iban',
        'swift_code',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
