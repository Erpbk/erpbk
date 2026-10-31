<?php

namespace App\Models;

use App\Services\RiderInvoice\RiderInvoiceViewDataBuilder;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiderInvoices extends BaseModel
{
    use LogsActivity, SoftDeletes;

    public $table = 'rider_invoices';

    public $fillable = [
        'company_id',
        'branch_id',
        'inv_date',
        'service_period_from',
        'service_period_to',
        'rider_id',
        'customer_id',
        'vendor_id',
        'zone',
        'login_hours',
        'working_days',
        'perfect_attendance',
        'rejection',
        'performance',
        'off',
        'month_invoice',
        'descriptions',
        'total_amount',
        'vat',
        'subtotal',
        'billing_month',
        'gaurantee',
        'notes',
        'terms_and_conditions',
        'customer_note',
        'status',
        'partial_paid_amount',
        'template_id',
        'deleted_by',
        'updated_by',
    ];

    protected $casts = [
        'inv_date' => 'date',
        'service_period_from' => 'date',
        'service_period_to' => 'date',
        'zone' => 'string',
        'perfect_attendance' => 'float',
        'performance' => 'string',
        'off' => 'string',
        'descriptions' => 'string',
        'total_amount' => 'float',
        // 'billing_month' => 'date',
        'gaurantee' => 'string',
        'notes' => 'string',
        'status' => 'integer',
        'partial_paid_amount' => 'array',
    ];

    /**
     * Cached outstanding summary for accessors (balance / paid_amount / deductions).
     *
     * @var array{
     *     final_amount: float,
     *     paid_amount: float,
     *     balance: float,
     *     deductions?: float,
     *     previous_balance?: float
     * }|null
     */
    protected $outstandingSummaryCache = null;

    /**
     * @param  array{
     *     final_amount: float,
     *     paid_amount: float,
     *     balance: float,
     *     deductions?: float,
     *     previous_balance?: float
     * }  $summary
     */
    public function setOutstandingSummary(array $summary): void
    {
        $this->outstandingSummaryCache = $summary;
    }

    /**
     * Balance due after deductions/additions — same figure shown on the Rider Invoice.
     */
    public function getBalanceAttribute()
    {
        return $this->outstandingSummary()['balance'];
    }

    /**
     * Payments already applied against this invoice
     * (mark-as-paid GL and/or linked Payment module rows).
     */
    public function getPaidAmountAttribute()
    {
        return $this->outstandingSummary()['paid_amount'];
    }

    /**
     * Month ledger deductions applied against this invoice period.
     */
    public function getDeductionsTotalAttribute(): float
    {
        return (float) ($this->outstandingSummary()['deductions'] ?? 0);
    }

    /**
     * Previous / carry-forward ledger balance before this invoice period.
     */
    public function getPreviousBalanceAttribute(): float
    {
        return (float) ($this->outstandingSummary()['previous_balance'] ?? 0);
    }

    /**
     * Same-month ledger deductions plus previous balance (when owed by the rider).
     */
    public function getTotalDeductionsAttribute(): float
    {
        $previous = $this->previous_balance;

        return round($this->deductions_total + ($previous > 0 ? $previous : 0), 2);
    }

    /**
     * Invoice total after deductions/additions — payable salary before payments.
     */
    public function getFinalAmountAttribute(): float
    {
        return (float) ($this->outstandingSummary()['final_amount'] ?? 0);
    }

    /**
     * @return array{
     *     final_amount: float,
     *     paid_amount: float,
     *     balance: float,
     *     deductions?: float,
     *     previous_balance?: float
     * }
     */
    protected function outstandingSummary(): array
    {
        if ($this->outstandingSummaryCache !== null) {
            return $this->outstandingSummaryCache;
        }

        $this->outstandingSummaryCache = app(RiderInvoiceViewDataBuilder::class)
            ->outstandingAmounts($this);

        return $this->outstandingSummaryCache;
    }

    public function getInvoiceNumberAttribute()
    {
        return 'RINV-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public static array $rules = [
        'inv_date' => 'required',
        'service_period_from' => 'required|date',
        'service_period_to' => 'required|date|after_or_equal:service_period_from',
        'rider_id' => 'required',
        'customer_id' => 'required',
        'vendor_id' => 'nullable',
        'zone' => 'required|string|max:191',
        'login_hours' => 'required',
        'working_days' => 'required',
        'perfect_attendance' => 'required|numeric',
        'rejection' => 'required',
        'performance' => 'required|string|max:20',
        'off' => 'required|string|max:20',
        'month_invoice' => 'nullable',
        'descriptions' => 'nullable|string|max:65535',
        'total_amount' => 'nullable|numeric',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'billing_month' => 'required',
        'gaurantee' => 'nullable|string|max:255',
        'notes' => 'nullable|string|max:500',
    ];

    /**
     * True when this invoice is marked paid (1) or partially paid (3).
     * Status is written only when a payment is recorded / edited / deleted,
     * or when the invoice is marked settled (paid without payment).
     */
    public function isPaid(): bool
    {
        return in_array((int) ($this->status ?? 0), [1, 3], true);
    }

    /**
     * Fully paid or settled — driven by stored status (1).
     */
    public function isFullyPaid(): bool
    {
        return (int) ($this->status ?? 0) === 1;
    }

    /**
     * Partially paid — driven by stored status (3).
     */
    public function isPartiallyPaid(): bool
    {
        return (int) ($this->status ?? 0) === 3;
    }

    /**
     * Listing / export / action badge key from stored status: paid | partial | unpaid.
     * Do not recompute from ledger here — money columns still use outstanding math.
     */
    public function paymentStatusKey(): string
    {
        return match ((int) ($this->status ?? 0)) {
            1 => 'paid',
            3 => 'partial',
            default => 'unpaid',
        };
    }

    /**
     * Invoices that can receive a payment (unpaid or partially paid).
     * Uses explicit status values because SQL `status != 1` excludes NULL rows.
     */
    public function scopePayable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhereIn('status', [0, 3]);
        });
    }

    /**
     * Clear cached outstanding figures after payment allocations change.
     */
    public function clearOutstandingSummary(): void
    {
        $this->outstandingSummaryCache = null;
    }

    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id');
        // return $this->hasOne(Riders::class, 'id', 'rider_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(RiderInvoiceItem::class, 'inv_id', 'id');
    }

    public function template()
    {
        return $this->belongsTo(RiderInvoiceTemplate::class, 'template_id');
    }
}
