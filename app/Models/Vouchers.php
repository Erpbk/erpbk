<?php

namespace App\Models;

use Eloquent as Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

/**
 * Class Vouchers
 * @package App\Models
 * @version January 23, 2024, 9:03 pm UTC
 *
 * @property string $trans_date
 * @property integer $trans_code
 * @property string $posting_date
 * @property string $billing_month
 * @property integer $payment_to
 * @property integer $payment_from
 * @property boolean $payment_type
 * @  boolean $voucher_type
 * @property string $reason
 * @property number $amount
 * @property string $remarks
 * @property integer $ref_id
 * @property integer $rider_id
 * @property integer $vendor_id
 * @property boolean $status
 */
class Vouchers extends Model
{
  use HasFactory, LogsActivity;

  public $table = 'vouchers';

  const CREATED_AT = 'created_at';
  const UPDATED_AT = 'updated_at';




  public $fillable = [
    'trans_date',
    'trans_code',
    'posting_date',
    'billing_month',
    'payment_to',
    'payment_from',
    'payment_type',
    'voucher_type',
    'Created_By',
    'invoice_voucher_type',
    'reason',
    'amount',
    'remarks',
    'ref_id',
    'rider_id',
    'vendor_id',
    'toll_gate',
    'trip_date',
    'direction',
    'lease_company',
    'attach_file',
    'Updated_By',
    'status'
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */


  /**
   * Validation rules
   *
   * @var array
   */
  public static $rules = [
    'trans_date' => 'nullable',
    'trans_code' => 'nullable',
    'posting_date' => 'nullable',
    'billing_month' => 'nullable',
    'payment_to' => 'nullable',
    'payment_from' => 'nullable',
    'payment_type' => 'nullable|numeric',
    'voucher_type' => 'nullable|numeric',
    'reason' => 'nullable|string|max:255',
    'amount' => 'nullable|numeric',
    'remarks' => 'nullable|string|max:255',
    'ref_id' => 'nullable',
    'rider_id' => 'nullable',
    'vendor_id' => 'nullable',
    'status' => 'nullable|numeric',
    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];

  public function transactions()
  {
    return $this->hasMany(Transactions::class, 'trans_code', 'trans_code');
  }

  public function getFormattedIdAttribute()
  {
    $prefix = $this->voucher_type ?: 'V';
    $number = str_pad($this->id, 4, '0', STR_PAD_LEFT);
    return "{$prefix}-{$number}";
  }
}
