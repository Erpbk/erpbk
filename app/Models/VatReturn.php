<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class VatReturn extends BaseModel
{
    use SoftDeletes;

    public const STATUS_UNPAID = 0;

    public const STATUS_PAID = 1;

    public const STATUS_PARTIAL = 3;

    /** Ledger reference_type for clearing entries posted when a return is filed (invoice-style). */
    public const LEDGER_REFERENCE_TYPE = 'VatReturn';

    protected $fillable = [
        'year',
        'quarter_slot',
        'quarter_label',
        'filed_at',
        'status',
        'partial_paid_amount',
        'filed_by',
        'deleted_by',
    ];

    protected $casts = [
        'filed_at' => 'datetime',
        'status' => 'integer',
        'partial_paid_amount' => 'array',
        'deleted_at' => 'datetime',
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
            ->whereNull('transactions.deleted_at')
            ->sum('transactions.debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->entries()->join('transactions', 'vat_return_entries.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->sum('transactions.credit');
    }

    /**
     * Gross payable from filed ledger entries (credit − debit).
     */
    public function getPayableAmountAttribute(): float
    {
        return round($this->total_credit - $this->total_debit, 2);
    }

    /**
     * Payment-form compatible invoice number (VTR-0001).
     */
    public function getInvoiceNumberAttribute(): string
    {
        return 'VTR-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Payment-form total (= gross payable).
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->payable_amount;
    }

    public function getTotalAttribute(): float
    {
        return $this->payable_amount;
    }

    public function getPaidAmountAttribute(): float
    {
        $partials = is_array($this->partial_paid_amount) ? $this->partial_paid_amount : [];

        return round((float) array_sum($partials), 2);
    }

    public function getBalanceAttribute(): float
    {
        return round($this->payable_amount - $this->paid_amount, 2);
    }

    /**
     * Approximate billing month for payment forms (last month of quarter).
     */
    public function getBillingMonthAttribute(): ?string
    {
        $slot = (int) ($this->quarter_slot ?? 0);
        if ($slot < 1 || $slot > 4) {
            return $this->filed_at?->format('Y-m-01');
        }
        $endMonth = $slot * 3;

        return sprintf('%04d-%02d-01', (int) $this->year, $endMonth);
    }

    public function isUnpaid(): bool
    {
        return (int) ($this->status ?? 0) === self::STATUS_UNPAID
            && $this->livePaymentIds() === [];
    }

    public function isPaid(): bool
    {
        return (int) ($this->status ?? 0) === self::STATUS_PAID;
    }

    public function isPartial(): bool
    {
        return (int) ($this->status ?? 0) === self::STATUS_PARTIAL;
    }

    public function hasLivePayments(): bool
    {
        return $this->livePaymentIds() !== [];
    }

    /**
     * @return list<int>
     */
    public function livePaymentIds(): array
    {
        $partials = is_array($this->partial_paid_amount) ? $this->partial_paid_amount : [];
        $ids = [];
        foreach ($partials as $paymentId => $amount) {
            if (abs((float) $amount) < 0.01) {
                continue;
            }
            $id = (int) $paymentId;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function statusLabel(): string
    {
        return match ((int) ($this->status ?? 0)) {
            self::STATUS_PAID => 'paid',
            self::STATUS_PARTIAL => 'partially_paid',
            default => 'unpaid',
        };
    }

    public function statusBadgeKey(): string
    {
        return match ((int) ($this->status ?? 0)) {
            self::STATUS_PAID => 'paid',
            self::STATUS_PARTIAL => 'partial',
            default => 'unpaid',
        };
    }

    /**
     * Recompute status from live partial_paid_amount vs payable.
     */
    public function syncStatusFromPartials(?float $payableOverride = null): void
    {
        $payable = $payableOverride !== null ? $payableOverride : $this->payable_amount;
        $paid = $this->paid_amount;
        $partials = is_array($this->partial_paid_amount) ? $this->partial_paid_amount : [];

        if ($partials === [] || $paid < 0.01) {
            $this->status = self::STATUS_UNPAID;
            $this->partial_paid_amount = $partials === [] ? null : $partials;
        } elseif (round($payable - $paid, 2) <= 0.01) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }
        $this->save();
    }

    /**
     * Allocate a payment amount onto this return and sync status.
     */
    public function allocatePayment(int $paymentId, float $amount): void
    {
        $partials = is_array($this->partial_paid_amount) ? $this->partial_paid_amount : [];
        $partials[(string) $paymentId] = round($amount, 2);
        $this->partial_paid_amount = $partials;
        $this->syncStatusFromPartials();
    }

    /**
     * Remove a payment allocation and sync status.
     */
    public function unallocatePayment(int $paymentId): void
    {
        $partials = is_array($this->partial_paid_amount) ? $this->partial_paid_amount : [];
        unset($partials[$paymentId], $partials[(string) $paymentId]);
        $this->partial_paid_amount = $partials === [] ? null : $partials;
        $this->syncStatusFromPartials();
    }

    /**
     * Clearing ledger rows posted for this return (reference_type = VatReturn).
     */
    public function clearingTransactions()
    {
        return Transactions::query()
            ->where('reference_type', self::LEDGER_REFERENCE_TYPE)
            ->where('reference_id', $this->id);
    }

    /**
     * Soft-delete clearing ledger rows linked to this return.
     */
    public function softDeleteLinkedClearing(?int $deletedBy = null): void
    {
        Transactions::softDeleteMatching(
            fn ($q) => $q->where('reference_type', self::LEDGER_REFERENCE_TYPE)
                ->where('reference_id', $this->id),
            $deletedBy
        );
    }

    /**
     * Restore clearing ledger rows linked to this return.
     */
    public function restoreLinkedClearing(): void
    {
        Transactions::onlyTrashed()
            ->where('reference_type', self::LEDGER_REFERENCE_TYPE)
            ->where('reference_id', $this->id)
            ->get()
            ->each(function (Transactions $txn) {
                $txn->restore();
                if (Schema::hasColumn($txn->getTable(), 'deleted_by') && $txn->deleted_by) {
                    $txn->deleted_by = null;
                    $txn->save();
                }
            });
    }
}
