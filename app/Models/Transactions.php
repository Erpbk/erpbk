<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Transactions extends Model
{
  use SoftDeletes, LogsActivity;

  protected $fillable = [
    'trans_code',
    'trans_date',
    'reference_id',
    'reference_type',
    'account_id',
    'debit',
    'credit',
    'billing_month',
    'narration',
    'deleted_by',
  ];

  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }
  function voucher()
  {
    return $this->hasOne(Vouchers::class, 'trans_code', 'trans_code');
  }

  public function supplierInvoice()
  {
    return $this->hasOne(SupplierInvoices::class, 'voucher_id');
  }
}
