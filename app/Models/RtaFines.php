<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class RtaFines extends BaseModel
{
    use LogsActivity, SoftDeletes, BranchScope;

  public $table = 'rta_fines';

  public $fillable = [
    'branch_id',
    'trans_date',
    'trans_code',
    'trip_date',
    'trip_time',
    'rider_id',
    'billing_month',
    'ticket_no',
    'bike_id',
    'plate_no',
    'detail',
    'amount',
    'service_charges',
    'admin_fee',
    'total_amount',
    'rta_account_id',
    'attachment',
    'attachment_path',
    'status',
    'reference_number',
    'vat',
    'rental_company_id',
    'fine_vat',
    'service_vat',
    'admin_vat',
    'voucher_id',
    'paid_voucher_id',
    'black_points',
    'is_impound',
  ];

  protected $casts = [
    'trans_date' => 'date',
    'trip_date' => 'date',
    'trip_time' => 'datetime',
    'billing_month' => 'date',
    'ticket_no' => 'string',
    'plate_no' => 'string',
    'detail' => 'string',
    'amount' => 'decimal:2',
    'service_charges' => 'decimal:2',
    'admin_fee' => 'decimal:2',
    'vat' => 'decimal:2',
    'total_amount' => 'decimal:2',
    'status' => 'string',
    'deleted_at' => 'datetime'
  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'trans_date' => 'nullable',
    'trans_code' => 'nullable',
    'trip_date' => 'required',
    'trip_time' => 'required',
    'rider_id' => 'nullable',
    'billing_month' => 'required',
    'ticket_no' => 'nullable|string|max:50',
    'bike_id' => 'required',
    'rta_account_id' => 'required',
    'attachment' => 'nullable',
    'attachment_path' => 'required',
    'plate_no' => 'nullable|string|max:50',
    'detail' => 'nullable|string|max:500',
    'amount' => 'required|numeric|min:1',
    'service_charges' => 'nullable|numeric',
    'vat'=> 'nullable|numeric',
    'admin_fee' => 'nullable|numeric',
    'total_amount' => 'nullable|numeric',
    'status' => 'nullable|string|max:20',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'branch_id' => 'nullable|exists:branches,id',
  ];

  public function rider()
  {
    return $this->belongsTo(Riders::class, 'rider_id', 'id');
  }
  public function bike()
  {
    return $this->belongsTo(Bikes::class, 'bike_id', 'id');
  }
  public function rtaAccount()
  {
    return $this->belongsTo(Accounts::class, 'rta_account_id', 'id');
  }
  function transactions()
  {
    return $this->hasMany(Transactions::class, 'trans_code', 'trans_code');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }

  public function rentalCompany()
  {
    return $this->belongsTo(BikeRentCompany::class, 'rental_company_id');
  }

  public function paidVoucher()
  {
    return $this->belongsTo(Vouchers::class, 'paid_voucher_id');
  }
}
