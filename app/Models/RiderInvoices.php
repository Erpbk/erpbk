<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RiderInvoices extends Model
{
    use LogsActivity;

  public $table = 'rider_invoices';

  public $fillable = [
    'inv_date',
    'rider_id',
    'vendor_id',
    'zone',
    'login_hours',
    'working_days',
    'perfect_attendance',
    'rejection',
    'performance',
    'off',
    'month_invoice',
    'descriptions',
    'total_amount',
    'vat',
    'subtotal',
    'billing_month',
    'gaurantee',
    'notes',
    'status'
  ];

  protected $casts = [
    'inv_date' => 'date',
    'zone' => 'string',
    'perfect_attendance' => 'float',
    'performance' => 'string',
    'off' => 'string',
    'descriptions' => 'string',
    'total_amount' => 'float',
    //'billing_month' => 'date',
    'gaurantee' => 'string',
    'notes' => 'string',
    'status' => 'integer'
  ];

  public static array $rules = [
    'inv_date' => 'required',
    'rider_id' => 'required',
    'vendor_id' => 'nullable',
    'zone' => 'required|string|max:191',
    'login_hours' => 'required',
    'working_days' => 'required',
    'perfect_attendance' => 'required|numeric',
    'rejection' => 'required',
    'performance' => 'required|string|max:20',
    'off' => 'required|string|max:20',
    'month_invoice' => 'nullable',
    'descriptions' => 'nullable|string|max:65535',
    'total_amount' => 'nullable|numeric',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'billing_month' => 'required',
    'gaurantee' => 'nullable|string|max:255',
    'notes' => 'nullable|string|max:500'
  ];

  public function rider()
  {
    return $this->belongsTo(Riders::class);
    //return $this->hasOne(Riders::class, 'id', 'rider_id');
  }
  public function items()
  {
    return $this->hasMany(RiderInvoiceItem::class, 'inv_id', 'id');
  }
}
