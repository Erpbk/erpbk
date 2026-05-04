<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Items extends BaseModel
{
  use LogsActivity, SoftDeletes;

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
    'deleted_by',
    'ref_name',
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
    'ref_name' => 'required',
    'supplier_id' => 'nullable|exists:suppliers,id',
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
  public function getOwnerAttribute()
  {
    if ($this->ref_name == 'customer')
      return Customers::find($this->ref_id);
    else if ($this->ref_name == 'supplier')
      return Supplier::find($this->ref_id);
    else if ($this->ref_name == 'leasingCompany')
      return LeasingCompanies::find($this->ref_id);
    else if ($this->ref_name == 'garage')
      return Garages::find($this->ref_id);
    else if ($this->ref_name == 'rider')
      return Riders::find($this->ref_id);
    else
      null;
  }
  public function supplier()
  {
    return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
  }
  public function deletedBy()
  {
    return $this->belongsTo(\App\Models\User::class, 'deleted_by', 'id');
  }
}
