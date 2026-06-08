<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCaseAccount extends BaseModel
{
    public $table = 'legal_case_accounts';

    public $fillable = ['account_id', 'name', 'rider_id', 'employee_id', 'company_id', 'branch_id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function isEmployeeAccount(): bool
    {
        return $this->employee_id !== null;
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(legal_cases::class, 'legal_case_account_id');
    }
}
