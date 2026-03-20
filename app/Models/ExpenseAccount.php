<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAccount extends Model
{
    public $table = 'expense_accounts';

    public $fillable = ['account_id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }
}
