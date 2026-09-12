<?php

namespace App\Models;

use App\Traits\LogsActivity;

class SimInvoiceItem extends BaseModel
{
    use LogsActivity;

    protected $table = 'sim_invoice_items';

    protected $fillable = [
        'inv_id',
        'sim_id',
        'item_id',
        'qty',
        'rate',
        'discount',
        'tax',
        'amount',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(SimInvoice::class, 'inv_id');
    }

    public function sim()
    {
        return $this->belongsTo(Sims::class, 'sim_id');
    }

    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id');
    }

    /** Excl-VAT charge for this line. */
    public function getExclAttribute(): float
    {
        return round(((float) $this->qty * (float) $this->rate) - (float) $this->discount, 2);
    }
}
