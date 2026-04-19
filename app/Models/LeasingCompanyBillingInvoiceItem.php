<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LeasingCompanyBillingInvoiceItem extends BaseModel
{
    use LogsActivity;

    protected $table = 'leasing_company_billing_invoice_items';

    protected $fillable = [
        'inv_id',
        'bike_id',
        'days',
        'rental_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
    ];

    protected $casts = [
        'days' => 'integer',
        'rental_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(LeasingCompanyBillingInvoice::class, 'inv_id');
    }

    public function bike()
    {
        return $this->belongsTo(Bikes::class, 'bike_id');
    }
}

