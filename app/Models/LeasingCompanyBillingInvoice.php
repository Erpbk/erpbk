<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeasingCompanyBillingInvoice extends Model
{
    use SoftDeletes, LogsActivity;

    public $table = 'leasing_company_billing_invoices';

    public $fillable = [
        'inv_date',
        'leasing_company_id',
        'billing_month',
        'invoice_number',
        'reference_number',
        'leasing_company_invoice_number',
        'descriptions',
        'subtotal',
        'vat',
        'total_amount',
        'notes',
        'attachment',
        'status',
        'partial_paid_amount'
    ];

    protected $casts = [
        'inv_date' => 'date',
        'billing_month' => 'date',
        'leasing_company_invoice_number' => 'string',
        'attachment' => 'string',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'integer',
        'partial_paid_amount' => 'array',
    ];

    protected $dates = ['deleted_at'];

    public static array $rules = [
        'inv_date' => 'required|date',
        'leasing_company_id' => 'required|exists:leasing_companies,id',
        'billing_month' => 'required|date',
        'invoice_number' => 'nullable|string|max:255',
        'reference_number' => 'required|string|max:255',
        'leasing_company_invoice_number' => 'required|string|max:255',
        'descriptions' => 'nullable|string',
        'notes' => 'nullable|string',
        'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        'status' => 'nullable|integer',
        'partial_paid_amount' => 'nullable|array',
    ];

    public function leasingCompany()
    {
        return $this->belongsTo(LeasingCompanies::class, 'leasing_company_id');
    }

    public function items()
    {
        return $this->hasMany(LeasingCompanyBillingInvoiceItem::class, 'inv_id', 'id');
    }

    public function getPaidAmountAttribute()
    {
        return array_sum($this->partial_paid_amount ?? []);
    }

    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public static function getIdFromInvoiceNumber($invoiceNumber)
    {
        // Remove the prefix 'CI-' and get the numeric part
        $numericPart = str_replace('LBI-', '', $invoiceNumber);
        
        // Remove leading zeros and convert to integer
        $id = (int) ltrim($numericPart, '0');
        
        // Verify the invoice exists
        return self::where('id', $id)->exists() ? $id : null;
    }
}

