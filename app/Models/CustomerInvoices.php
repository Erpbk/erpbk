<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoices extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inv_date',
        'customer_id',
        'billing_month',
        'date_from',
        'date_to',
        'reference',
        'description',
        'notes',
        'subtotal',
        'vat',
        'total',
        'attachment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inv_date' => 'date',
        'date_from' => 'date',
        'date_to' => 'date',
        'billing_month' => 'date',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the invoice.
     */
    public function customer()
    {
        return $this->belongsTo(Customers::class,'customer_id','id');
    }

    /**
     * Get the items for the invoice.
     */
    public function items()
    {
        return $this->hasMany(CustomerInvoiceItem::class, 'inv_id');
    }

    /**
     * Scope a query to only include invoices for a specific company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to only include invoices for a specific billing month.
     */
    public function scopeForBillingMonth($query, $billingMonth)
    {
        return $query->where('billing_month', $billingMonth);
    }

    /**
     * Get the formatted invoice number (you can customize this)
     */
    public function getInvoiceNumberAttribute()
    {
        return 'CI-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}