<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimInvoice extends BaseModel
{
    use SoftDeletes, LogsActivity;

    public $table = 'sim_invoices';

    public $fillable = [
        'inv_date',
        'vendor_id',
        'billing_month',
        'invoice_number',
        'reference_number',
        'sim_invoice_number',
        'descriptions',
        'subtotal',
        'vat',
        'total_amount',
        'notes',
        'attachment',
        'status',
        'partial_paid_amount',
    ];

    protected $casts = [
        'inv_date' => 'date',
        'billing_month' => 'date',
        'sim_invoice_number' => 'string',
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
        'vendor_id' => 'required|exists:sim_companies,id',
        'billing_month' => 'required|date',
        'invoice_number' => 'nullable|string|max:255',
        'reference_number' => 'required|string|max:255',
        'sim_invoice_number' => 'nullable|string|max:255',
        'descriptions' => 'nullable|string',
        'notes' => 'nullable|string',
        'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        'status' => 'nullable|integer',
    ];

    public function vendor()
    {
        return $this->belongsTo(SimCompany::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(SimInvoiceItem::class, 'inv_id', 'id');
    }

    public function getInvoiceNumberAttribute()
    {
        return 'SIMI' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public static function getIdFromInvoiceNumber($invoiceNumber)
    {
        $numericPart = str_replace('SIMI', '', $invoiceNumber);
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
