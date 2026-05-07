<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseAccount extends BaseModel
{
    public $table = 'expense_accounts';

    public $fillable = ['account_id', 'name', 'rider_id', 'company_id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function visaExpenses(): HasMany
    {
        return $this->hasMany(visa_expenses::class, 'expense_account_id');
    }
}
