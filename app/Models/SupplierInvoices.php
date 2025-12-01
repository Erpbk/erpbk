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

  ];

  protected $casts = [
    'zone' => 'string',
    'perfect_attendance' => 'float',
    'performance' => 'string',
    'off' => 'string',
    'descriptions' => 'string',
    'total_amount' => 'float',
    'notes' => 'string'
  ];

  public static array $rules = [
    'inv_date' => 'required',
    'supplier_id' => 'required',
    'month_invoice' => 'nullable',
    'descriptions' => 'nullable|string|max:65535',
    'total_amount' => 'nullable|numeric',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'billing_month' => 'required',
    'notes' => 'nullable|string|max:500'
  ];
  
  
  
  protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $lastInvoice = self::orderBy('id', 'desc')->first();
            $lastNumber = 0;

            if ($lastInvoice && $lastInvoice->inv_id) {
                $lastNumber = (int) str_replace('SUP', '', $lastInvoice->inv_id);
            }

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
}
