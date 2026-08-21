<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryPurchase extends BaseModel
{
    use SoftDeletes;
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

    public function isUsed(): bool
    {
        return (float) $this->remaining_quantity < (float) $this->quantity;
    }

    public function usedQuantity(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->remaining_quantity, 2));
    }

    public static function hasBeenUsedForInvoice($invoiceId): bool
    {
        $purchases = static::where('inv_id', $invoiceId)->get();
        if ($purchases->isEmpty()) {
            return false;
        }

        return $purchases->contains(function ($purchase) {
            return $purchase->isUsed();
        });
    }

    public static function canReassignUsageForInvoice($invoiceId): bool
    {
        $purchases = static::where('inv_id', $invoiceId)->orderBy('id')->get();
        $excludeIds = $purchases->pluck('id')->all();

        foreach ($purchases as $purchase) {
            if ($purchase->isUsed() && ! $purchase->canReassignUsage($excludeIds)) {
                return false;
            }
        }

        return true;
    }

    public static function reassignUsageForInvoice($invoiceId): bool
    {
        $purchases = static::where('inv_id', $invoiceId)->orderBy('id')->lockForUpdate()->get();
        $excludeIds = $purchases->pluck('id')->all();
        $allOk = true;

        foreach ($purchases as $purchase) {
            if (! $purchase->isUsed()) {
                continue;
            }
            if (! $purchase->reassignUsage($excludeIds)) {
                $allOk = false;
            }
        }

        return $allOk;
    }

    public function canReassignUsage(array $excludePurchaseIds = []): bool
    {
        return $this->planUsageReassignment($excludePurchaseIds) !== null;
    }

    public function reassignUsage(array $excludePurchaseIds = []): bool
    {
        $plan = $this->planUsageReassignment($excludePurchaseIds, true);
        if ($plan === null) {
            return false;
        }
        if ($plan === []) {
            return true;
        }

        $this->applyUsageReassignment($plan);

        return true;
    }

    private function planUsageReassignment(array $excludePurchaseIds = [], bool $forUpdate = false)
    {
        if (! $this->isUsed()) {
            return [];
        }

        $usedQty = $this->usedQuantity();
        if ($usedQty <= 0) {
            return [];
        }

        $alternates = $this->alternatePurchases($excludePurchaseIds, $forUpdate);
        $single = $alternates->first(function ($purchase) use ($usedQty) {
            return round((float) $purchase->remaining_quantity, 2) >= $usedQty;
        });

        if ($single) {
            return [
                'mode' => 'single',
                'target' => $single,
                'qty' => $usedQty,
            ];
        }

        if (round((float) $alternates->sum('remaining_quantity'), 2) < $usedQty) {
            return null;
        }

        $moves = $this->planDistributedMoves($alternates);
        if ($moves === null) {
            return null;
        }

        return [
            'mode' => 'distribute',
            'moves' => $moves,
            'qty' => $usedQty,
        ];
    }

    private function alternatePurchases(array $excludePurchaseIds, bool $forUpdate)
    {
        $excludeIds = array_values(array_unique(array_filter(array_merge($excludePurchaseIds, [$this->id]))));

        $query = static::query()
            ->where('item_id', $this->item_id)
            ->where('garage_id', $this->garage_id)
            ->whereNotIn('id', $excludeIds)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('purchase_date')
            ->orderBy('id');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    private function planDistributedMoves($alternates)
    {
        $pool = [];
        foreach ($alternates as $purchase) {
            $pool[] = [
                'purchase' => $purchase,
                'remaining' => round((float) $purchase->remaining_quantity, 2),
            ];
        }

        $moves = [];
        $related = $this->maintenanceItems()->orderBy('id')->get();
        if ($related->isEmpty()) {
            return null;
        }

        foreach ($related as $record) {
            $need = round((float) $record->quantity, 2);
            $slot = null;
            foreach ($pool as $index => $candidate) {
                if ($candidate['remaining'] >= $need) {
                    $slot = $index;
                    break;
                }
            }
            if ($slot === null) {
                return null;
            }

            $moves[] = [
                'record' => $record,
                'target' => $pool[$slot]['purchase'],
                'qty' => $need,
            ];
            $pool[$slot]['remaining'] = round($pool[$slot]['remaining'] - $need, 2);
        }

        return $moves;
    }

    private function applyUsageReassignment(array $plan): void
    {
        if (($plan['mode'] ?? null) === 'single') {
            $target = $plan['target'];
            BikeMaintenanceItem::where('inventory_purchase_id', $this->id)
                ->update(['inventory_purchase_id' => $target->id]);
            InventoryAdjustment::where('inventory_purchase_id', $this->id)
                ->update(['inventory_purchase_id' => $target->id]);
            $target->decrement('remaining_quantity', $plan['qty']);
        } elseif (($plan['mode'] ?? null) === 'distribute') {
            $firstTarget = null;
            foreach ($plan['moves'] as $move) {
                $move['record']->update(['inventory_purchase_id' => $move['target']->id]);
                $move['target']->decrement('remaining_quantity', $move['qty']);
                $firstTarget = $firstTarget ?? $move['target'];
            }
            if ($firstTarget) {
                InventoryAdjustment::where('inventory_purchase_id', $this->id)
                    ->update(['inventory_purchase_id' => $firstTarget->id]);
            }
        }

        $this->remaining_quantity = $this->quantity;
        $this->save();
    }
    
    public function getAvailableQuantityAttribute()
    {
        $adjustedOut = $this->adjustments()->sum('quantity');
        $usedInMaintenance = $this->maintenanceItems()->sum('quantity');
        return $this->remaining_quantity - $adjustedOut - $usedInMaintenance;
    }
}