<?php

namespace App\Models;

class InventoryAdjustment extends BaseModel
{
    protected $table = 'inventory_adjustments';
    
    public $timestamps = false;
    
    protected $fillable = [
        'inventory_purchase_id', 'adjustment_type', 'quantity',
        'reason', 'reference_number', 'adjusted_by', 'adjustment_date', 'notes'
    ];
    
    protected $casts = [
        'adjustment_date' => 'datetime'
    ];
    
    public function purchase()
    {
        return $this->belongsTo(InventoryPurchase::class, 'inventory_purchase_id');
    }
    
    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}