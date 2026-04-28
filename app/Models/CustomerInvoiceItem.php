<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoiceItem extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inv_id',
        'item_id',
        'item_name',
        'quantity',
        'rate',
        'vat',
        'vat_amount',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'vat' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the invoice that owns the item.
     */
    public function invoice()
    {
        return $this->belongsTo(CustomerInvoices::class, 'inv_id', 'id');
    }

    /**
     * Get the item (if linked to items table).
     */
    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id');
    }

    /**
     * Scope a query to only include items for a specific invoice.
     */
    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('inv_id', $invoiceId);
    }
}