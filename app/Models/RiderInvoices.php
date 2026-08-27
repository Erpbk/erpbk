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
        'status',
        'template_id',
        'deleted_by',
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
    ];

    /**
     * Cached outstanding summary for accessors (balance / paid_amount).
     *
     * @var array{final_amount: float, paid_amount: float, balance: float}|null
     */
    protected $outstandingSummaryCache = null;

    /**
     * Balance due after deductions/additions — same figure shown on the Rider Invoice.
     */
    public function getBalanceAttribute()
    {
        return $this->outstandingSummary()['balance'];
    }

    /**
     * Payments already applied for this invoice's billing month.
     */
    public function getPaidAmountAttribute()
    {
        return $this->outstandingSummary()['paid_amount'];
    }

    /**
     * @return array{final_amount: float, paid_amount: float, balance: float}
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
        return 'RINV-'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public static array $rules = [
        'inv_date' => 'required',
        'service_period_from' => 'required|date',
        'service_period_to' => 'required|date|after_or_equal:service_period_from',
        'rider_id' => 'required',
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
     * Any recorded payment settles the invoice (status 1). Status 3 is a legacy
     * "partially paid" value and is also treated as settled.
     */
    public function isPaid(): bool
    {
        if (in_array((int) $this->status, [1, 3], true)) {
            return true;
        }

        return (float) ($this->paid_amount ?? 0) > 0;
    }

    /**
     * Invoices that can receive a payment (unpaid only).
     * Uses explicit status values because SQL `status != 1` excludes NULL rows.
     */
    public function scopePayable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhere('status', 0);
        });
    }

    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id');
        // return $this->hasOne(Riders::class, 'id', 'rider_id');
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
