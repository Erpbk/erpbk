<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class legal_cases extends BaseModel
{
    use LogsActivity, SoftDeletes;

    protected $table = 'legal_cases';

    protected $fillable = [
        'date',
        'rider_id',
        'employee_id',
        'case_status',
        'detail',
        'branch_id',
        'reference_number',
        'billing_month',
        'step_status',
        'expiry_date',
        'deleted_by',
        'legal_case_account_id',
        'company_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public static array $rules = [
        'date' => 'required',
        'rider_id' => 'nullable',
        'billing_month' => 'required',
        'case_status' => 'nullable|string|max:50',
        'detail' => 'nullable|string|max:500',
        'expiry_date' => 'nullable|date',
        'branch_id' => 'nullable',
    ];

    public function rider()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }

    public function legalCaseAccount()
    {
        return $this->belongsTo(LegalCaseAccount::class, 'legal_case_account_id');
    }

    public function account()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }

    public function isPending(): bool
    {
        return $this->step_status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->step_status === 'completed';
    }
}
