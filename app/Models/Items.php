<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Items extends Model
{
  use LogsActivity;

  public $table = 'items';

  public $fillable = [
    'name',
    'item_unit',
    'detail',
    'price',
    'cost',
    'vat',
    'code',
    'barcode',
    'created_by',
    'updated_by',
    'customer_id',
    'supplier_id',
    'status'
  ];

  protected $casts = [
    'name' => 'string',
    'detail' => 'string',
    'price' => 'decimal:2',
    'cost' => 'decimal:2',
    'vat' => 'decimal:2'
  ];

  public static array $rules = [
    'name' => 'required|string|max:255',
    'customer_id' => 'required',
    'supplier_id' => 'required',
    'detail' => 'nullable|string|max:500',
    'price' => 'required|numeric',
    'cost' => 'required|numeric',
    'vat' => 'nullable|numeric',
    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];

  public static function dropdown()
  {
    $query = self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');
    return $query;
  }
  public function customer()
  {
    return $this->belongsTo(Customers::class, 'customer_id', 'id');
  }
  public function supplier()
  {
    return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
  }
}
