<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class LeasingCompanyInvoice extends Model
{
    use SoftDeletes, LogsActivity;

    public $table = 'leasing_company_invoices';

    public $fillable = [
        'inv_date',
        'leasing_company_id',
        'billing_month',
        'invoice_number',
        'descriptions',
        'subtotal',
        'vat',
        'total_amount',
        'notes',
        'status'
    ];

    protected $casts = [
        'inv_date' => 'date',
        'billing_month' => 'date',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    public static array $rules = [
        'inv_date' => 'required|date',
        'leasing_company_id' => 'required|exists:leasing_companies,id',
        'billing_month' => 'required|date',
        'invoice_number' => 'nullable|string|max:255',
        'descriptions' => 'nullable|string',
        'notes' => 'nullable|string',
        'status' => 'nullable|integer'
    ];

    public function leasingCompany()
    {
        return $this->belongsTo(LeasingCompanies::class, 'leasing_company_id');
    }

    public function items()
    {
        return $this->hasMany(LeasingCompanyInvoiceItem::class, 'inv_id', 'id');
    }
}
