<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VatReturn extends Model
{
    protected $fillable = [
        'year',
        'quarter_slot',
        'quarter_label',
        'filed_at',
        'status',
        'filed_by',
    ];

    protected $casts = [
        'filed_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(VatReturnEntry::class);
    }

    public function transactions()
    {
        return $this->belongsToMany(Transactions::class, 'vat_return_entries', 'vat_return_id', 'transaction_id')
            ->withTimestamps();
    }

    public function filedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'filed_by');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->entries()->join('transactions', 'vat_return_entries.transaction_id', '=', 'transactions.id')
            ->sum('transactions.debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->entries()->join('transactions', 'vat_return_entries.transaction_id', '=', 'transactions.id')
            ->sum('transactions.credit');
    }

    public function getPayableAmountAttribute(): float
    {
        return $this->total_credit - $this->total_debit;
    }
}
