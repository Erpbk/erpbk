<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class Loan extends BaseModel
{
    use LogsActivity, SoftDeletes, BranchScope;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_DEFAULTED = 'defaulted';

    public const INTEREST_FLAT = 'flat';

    public const INTEREST_REDUCING = 'reducing_balance';

    public $table = 'loans';

    public $fillable = [
        'branch_id',
        'loan_number',
        'bank_id',
        'receiving_bank_id',
        'paying_bank_id',
        'account_id',
        'principal_amount',
        'disbursed_amount',
        'processing_fee',
        'interest_rate',
        'interest_calculation_method',
        'term_months',
        'repayment_frequency',
        'disbursement_date',
        'first_payment_date',
        'maturity_date',
        'status',
        'outstanding_principal',
        'emi_amount',
        'agreement_ref',
        'notes',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'outstanding_principal' => 'decimal:2',
        'emi_amount' => 'decimal:2',
        'disbursement_date' => 'date',
        'first_payment_date' => 'date',
        'maturity_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public static array $rules = [
        'bank_id' => 'required|exists:banks,id',
        'receiving_bank_id' => 'nullable|exists:banks,id',
        'paying_bank_id' => 'nullable|exists:banks,id',
        'principal_amount' => 'required|numeric|min:0.01',
        'processing_fee' => 'nullable|numeric|min:0',
        'interest_rate' => 'required|numeric|min:0',
        'interest_calculation_method' => 'required|in:flat,reducing_balance',
        'term_months' => 'required|integer|min:1|max:600',
        'first_payment_date' => 'required|date',
        'agreement_ref' => 'nullable|string|max:255',
        'notes' => 'nullable|string|max:65535',
        'branch_id' => 'nullable|integer',
    ];

    public function bank()
    {
        return $this->belongsTo(Banks::class, 'bank_id');
    }

    public function receivingBank()
    {
        return $this->belongsTo(Banks::class, 'receiving_bank_id');
    }

    public function payingBank()
    {
        return $this->belongsTo(Banks::class, 'paying_bank_id');
    }

    public function account()
    {
        return $this->hasOne(Accounts::class, 'id', 'account_id');
    }

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id')->orderBy('installment_no');
    }

    public function pendingInstallments()
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id')
            ->whereIn('status', [LoanInstallment::STATUS_PENDING, LoanInstallment::STATUS_OVERDUE, LoanInstallment::STATUS_PARTIAL])
            ->orderBy('due_date');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
            self::STATUS_CLOSED => '<span class="badge bg-secondary">Closed</span>',
            self::STATUS_DEFAULTED => '<span class="badge bg-danger">Defaulted</span>',
            default => '<span class="badge bg-warning">Draft</span>',
        };
    }

    public static function interestCalculationMethods(): array
    {
        return [
            self::INTEREST_REDUCING => 'Reducing Balance',
            self::INTEREST_FLAT => 'Flat Interest Rate',
        ];
    }

    public function getInterestCalculationMethodLabelAttribute(): string
    {
        return self::interestCalculationMethods()[$this->interest_calculation_method]
            ?? ucwords(str_replace('_', ' ', (string) $this->interest_calculation_method));
    }

    public function usesFlatInterest(): bool
    {
        return $this->interest_calculation_method === self::INTEREST_FLAT;
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && ! $this->installments()->where('status', LoanInstallment::STATUS_PAID)->exists();
    }

    public static function generateLoanNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'LN-'.$year.'-';
        $last = static::withTrashed()
            ->where('loan_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('loan_number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
