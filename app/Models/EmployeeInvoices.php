<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
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
        'subtotal' =>  'float',
        'vat' => 'float',
        'status' => 'integer',
        'partial_paid_amount' => 'array',
    ];

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

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function items()
    {
        return $this->hasMany(EmployeeInvoiceItem::class, 'inv_id', 'id');
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

    public function getPaidAmountAttribute()
    {
        return array_sum($this->partial_paid_amount ?? []);
    }

    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
}

