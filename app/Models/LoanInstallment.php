<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class LoanInstallment extends BaseModel
{
    use LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_OVERDUE = 'overdue';

    public $table = 'loan_installments';

    public $fillable = [
        'loan_id',
        'installment_no',
        'due_date',
        'opening_balance',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'late_payment_charges',
        'status',
        'paid_amount',
        'paid_date',
        'voucher_id',
        'payment_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'opening_balance' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'late_payment_charges' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Vouchers::class, 'voucher_id');
    }

    public function installmentTransactions()
    {
        return $this->hasMany(Transactions::class, 'reference_id', 'id')
            ->where('reference_type', 'BL');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => '<span class="badge bg-success">Paid</span>',
            self::STATUS_PARTIAL => '<span class="badge bg-info">Partial</span>',
            self::STATUS_OVERDUE => '<span class="badge bg-danger">Overdue</span>',
            default => '<span class="badge bg-warning">Pending</span>',
        };
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE, self::STATUS_PARTIAL]);
    }

    public function canBePaid(): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false;
        }

        return $this->loan && $this->loan->status === Loan::STATUS_ACTIVE;
    }

    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_OVERDUE) {
            return true;
        }

        if ($this->status === self::STATUS_PAID || ! $this->due_date) {
            return false;
        }

        return $this->due_date->lt(now()->startOfDay());
    }

    public function outstandingPrincipalAfter(): float
    {
        return max(0, round((float) $this->opening_balance - (float) $this->principal_amount, 2));
    }
}