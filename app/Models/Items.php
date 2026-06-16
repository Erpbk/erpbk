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
    'owner',
    'supplier_id',
    'status',
    'attachment',
    'inventory_purchase_id',
    'cost',
    'total_cost',
    'profit',
    'is_maintained'
  ];

  protected $casts = [
    'name' => 'string',
    'detail' => 'string',
    'price' => 'decimal:2',
    'cost' => 'decimal:2',
    'vat' => 'decimal:2',
    'owner' => 'array',
    'is_maintained' => 'boolean'
  ];

  public static array $rules = [
    'name' => 'required|string|max:255',
    'owner' => 'required',
    'supplier_id' => 'nullable|exists:suppliers,id',
    'detail' => 'nullable|string|max:500',
    'price' => 'required|numeric',
    'cost' => 'required|numeric',
    'vat' => 'nullable|numeric',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'attachment' => 'nullable'
  ];

  public static function dropdown($type)
  {
    $items = self::where('status',1)->whereJsonContains('owner',$type)->get();
    return $items;
  }

  public static function availableForAssignment()
  {
    return self::dropdown('riderInventory');
  }
  public function getOwnersAttribute()
  {
    if(!$this->owner)
      return null;
    if (is_array($this->owner) && !empty($this->owner)) {
      
        $typeColors = [
            'customer' => '#28a745',      // Green
            'supplier' => '#007bff',      // Blue
            'leasingCompany' => '#ffc107', // Yellow
            'garage' => '#dc3545',        // Red
            'rider' => '#17a2b8',         // Teal
            'employee' => '#6610f2',      // Purple
            'riderInventory' => '#fd7e14', // Orange
        ];
        
        $ownerNames = [];
        
        foreach ($this->owner as $type) {

              $color = $typeColors[$type] ?? '#6c757d';
              $ownerNames[] =  '<span style="color: ' . $color . '; font-weight: bold;">' . ucfirst($type) . '</span>';
            }
        
        return $ownerNames;
    }
    
    return null;
  }
  public function supplier()
  {
    return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
  }
  public function deletedBy()
  {
    return $this->belongsTo(\App\Models\User::class, 'deleted_by', 'id');
  }

  public function purchaseHistory()
  {
    return InventoryPurchase::where('item_id', $this->id)->orderBy('purchase_date','desc')->get();
  }

  public function getAvailableAttribute()
  {
    return InventoryPurchase::where('item_id', $this->id)->sum('remaining_quantity') ?? 0;
  }

  public function getAvailableInventory()
  {
    return InventoryPurchase::where('item_id', $this->id)->where('remaining_quantity','>',0)->get();
  }
}
