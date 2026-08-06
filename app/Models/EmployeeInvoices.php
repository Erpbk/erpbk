<?php

namespace App\Models;

use App\Services\EmployeeInvoice\EmployeeInvoiceViewDataBuilder;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeInvoices extends BaseModel
{
    use SoftDeletes, LogsActivity;

    public $table = 'employee_invoices';

    protected $fillable = [
        'inv_date',
        'employee_id',
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
        'partial_paid_amount',
        'deleted_by',
    ];

    protected $casts = [
        'inv_date' => 'date',
        'perfect_attendance' => 'float',
        'total_amount' => 'float',
        'subtotal' => 'float',
        'vat' => 'float',
        'status' => 'integer',
        'partial_paid_amount' => 'array',
    ];

    /**
     * Cached outstanding summary for accessors (balance / paid_amount).
     *
     * @var array{final_amount: float, paid_amount: float, balance: float}|null
     */
    protected $outstandingSummaryCache = null;

    public static array $rules = [
        'inv_date' => 'required',
        'employee_id' => 'required|exists:employees,id',
        'zone' => 'nullable|string|max:191',
        'login_hours' => 'nullable',
        'working_days' => 'nullable',
        'perfect_attendance' => 'nullable|numeric',
        'rejection' => 'nullable',
        'performance' => 'nullable|string|max:20',
        'off' => 'nullable|string|max:20',
        'descriptions' => 'nullable|string|max:65535',
        'billing_month' => 'required',
        'gaurantee' => 'nullable|string|max:255',
        'notes' => 'nullable|string|max:500',
    ];

    /**
     * Balance due after deductions/additions — same figure shown on the Employee Invoice.
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

        if (! $this->relationLoaded('employee')) {
            $this->load('employee');
        }
        if (! $this->relationLoaded('items')) {
            $this->load('items');
        }

        $this->outstandingSummaryCache = app(EmployeeInvoiceViewDataBuilder::class)
            ->outstandingAmounts($this);

        return $this->outstandingSummaryCache;
    }

    public function getInvoiceNumberAttribute()
    {
        return 'EMP_INV' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public static function getIdFromInvoiceNumber($invoiceNumber)
    {
        $numericPart = str_replace('EMP_INV', '', $invoiceNumber);
        $id = (int) ltrim($numericPart, '0');

        return self::where('id', $id)->exists() ? $id : null;
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

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function items()
    {
        return $this->hasMany(EmployeeInvoiceItem::class, 'inv_id', 'id');
    }
}
