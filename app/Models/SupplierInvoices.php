<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SupplierInvoices extends Model
{
    use LogsActivity;

  public $table = 'supplier_invoices';

  public $fillable = [
    'inv_date',
    'supplier_id',
    'month_invoice',
    'descriptions',
    'total_amount',
    'billing_month',
    'notes',
    'inv_id',
    'subtotal',
    'vat',
    'partial_paid_amount',
    'status',
    'is_order',
    'is_invoice',
    'attachment',
    'created_by',
    'updated_by',
    'order_date'
  ];

  protected $casts = [
    'partial_paid_amount' => 'array',
    'descriptions' => 'string',
    'total_amount' => 'float',
    'notes' => 'string',
    'billing_month' => 'date',
    'inv_date' => 'date',
    'order_date' => 'date',
  ];

  public static array $rules = [
    'inv_date' => 'nullable',
    'supplier_id' => 'required',
    'month_invoice' => 'nullable',
    'descriptions' => 'nullable|string|max:65535',
    'total_amount' => 'nullable|numeric',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'billing_month' => 'nullable',
    'notes' => 'nullable|string|max:500',
  ];
  
  
  
  protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $lastInvoice = self::orderBy('id', 'desc')->first();
            $lastNumber = $lastInvoice->id;

            $invoice->inv_id = 'SUP' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        });
    }

  public function supplier()
  {
    return $this->belongsTo(Supplier::class);
  }

  public function items()
  {
      return $this->hasMany(SupplierInvoicesItem::class, 'inv_id');
  }

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  public function getInvoiceNumberAttribute()
  {
    return $this->inv_id;
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
    $numericPart = str_replace('SUP', '', $invoiceNumber);
    
    // Remove leading zeros and convert to integer
    $id = (int) ltrim($numericPart, '0');
    
    // Verify the invoice exists
    return self::where('id', $id)->exists() ? $id : null;
  }
}
