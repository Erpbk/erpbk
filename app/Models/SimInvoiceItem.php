<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SimInvoiceItem extends BaseModel
{
    use LogsActivity;

    protected $table = 'sim_invoice_items';

    protected $fillable = [
        'inv_id',
        'sim_id',
        'rental_amount',
        'additional_charges',
        'international_usage_charges',
        'tax_rate',
        'tax_amount',
        'total_amount',
    ];

    protected $casts = [
        'rental_amount' => 'decimal:2',
        'additional_charges' => 'decimal:2',
        'international_usage_charges' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(SimInvoice::class, 'inv_id');
    }

    public function sim()
    {
        return $this->belongsTo(Sims::class, 'sim_id');
    }
}
