<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BranchScope;

class CustomerInvoices extends Model
{
    use HasFactory, SoftDeletes, BranchScope;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
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
        'status',
        'partial_paid_amount',
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
        'partial_paid_amount' => 'array',
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
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
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
        return 'CI-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public static function getIdFromInvoiceNumber($invoiceNumber)
    {
        // Remove the prefix 'CI-' and get the numeric part
        $numericPart = str_replace('CI-', '', $invoiceNumber);
        
        // Remove leading zeros and convert to integer
        $id = (int) ltrim($numericPart, '0');
        
        // Verify the invoice exists
        return self::where('id', $id)->exists() ? $id : null;
    }

    public function getPaidAmountAttribute()
    {
        return array_sum($this->partial_paid_amount ?? []);
    }

    public function getBalanceAttribute()
    {
        return $this->total - $this->paid_amount;
    }

}