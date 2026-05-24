<?php

namespace App\Models;

class InventoryPurchase extends BaseModel
{
    protected $table = 'inventory_purchases';
    
    protected $fillable = [
        'item_id', 'supplier_id', 'item_name' , 'purchase_date', 'inv_id',
        'quantity', 'remaining_quantity', 'unit_cost', 'batch_no',
        'notes', 'created_by', 'deleted_at', 'garage_id'
    ];

    protected $casts = [
        'purchase_date' => 'date'
    ];
    
    public function item()
    {
        return $this->belongsTo(Items::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function adjustments()
    {
        return $this->hasMany(InventoryAdjustment::class, 'inventory_purchase_id');
    }
    
    public function maintenanceItems()
    {
        return $this->hasMany(BikeMaintenanceItem::class, 'inventory_purchase_id');
    }

    public function garage()
    {
        return $this->belongsTo(Garages::class, 'garage_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoices::class, 'inv_id');
    }
    
    public function getAvailableQuantityAttribute()
    {
        $adjustedOut = $this->adjustments()->sum('quantity');
        $usedInMaintenance = $this->maintenanceItems()->sum('quantity');
        return $this->remaining_quantity - $adjustedOut - $usedInMaintenance;
    }
}