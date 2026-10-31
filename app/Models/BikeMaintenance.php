<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

use App\Traits\TracksUserAttribution;

class BikeMaintenance extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes, TracksUserAttribution;

    public $table = 'bike_maintenances';

    public $fillable = [
        'bike_id',
        'rider_id',
        'rental_company_id',
        'garage_id',
        'leasing_company_id',
        'maintenance_date',
        'description',
        'current_km',
        'previous_km',
        'overdue_km',
        'overdue_cost_per_km',
        'total_cost',
        'overdue_paidby',
        'paidby',
        'billing_month',
        'attachment',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'branch_id',
    ];

    protected $casts = [
        'maintenance_date' => 'datetime',
        'billing_month' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function bike()
    {
        return $this->belongsTo(Bikes::class, 'bike_id', 'id');
    }

    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id', 'id');
    }

    public function garage()
    {
        return $this->belongsTo(Garages::class, 'garage_id', 'id');
    }

    public function leasingCompany()
    {
        return $this->belongsTo(LeasingCompanies::class, 'leasing_company_id', 'id');
    }

    /**
     * True when performer is a leasing company or a non-internal garage.
     */
    public function isExternalPerformer(): bool
    {
        if ($this->leasing_company_id) {
            return true;
        }

        return $this->garage && $this->garage->garage_type !== 'internal';
    }

    public function isInternalGaragePerformer(): bool
    {
        return (bool) ($this->garage && $this->garage->garage_type === 'internal');
    }

    public function performerName(): ?string
    {
        if ($this->leasingCompany) {
            return $this->leasingCompany->name;
        }

        return $this->garage?->name;
    }

    public function maintenanceItems()
    {
        return $this->hasMany(BikeMaintenanceItem::class, 'bike_maintenance_id', 'id');
    }

    public function rentalCompany()
    {
        return $this->belongsTo(BikeRentCompany::class, 'rental_company_id');
    }

    public function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function getCostAttribute()
    {
        return BikeMaintenanceItem::where('bike_maintenance_id', $this->id)->sum('total_amount');
    }

    /**
     * Rebuild bike meter fields from non-deleted scheduled maintenance history.
     */
    public static function syncBikeMeterFieldsForBike(?Bikes $bike, $maintenanceKm = null): void
    {
        if (! $bike) {
            return;
        }

        $scheduled = self::query()
            ->where('bike_id', $bike->id)
            ->where('current_km', '>', 0)
            ->orderByDesc('maintenance_date')
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        $latest = $scheduled->get(0);
        $prior = $scheduled->get(1);

        $data = [
            'current_km' => $latest?->current_km,
            'previous_km' => $prior?->current_km ?? ($latest?->previous_km),
        ];

        if ($maintenanceKm !== null) {
            $data['maintenance_km'] = $maintenanceKm;
        }

        $bike->update($data);
    }

    /**
     * After the maintenance is soft-deleted (approval or bypass): return stock,
     * soft-delete line items + ledger rows, and refresh bike meters.
     */
    public function finalizeSoftDeletion(?int $deletedBy = null): void
    {
        $wasBypassing = \App\Services\DeleteRequestService::isBypassing();
        \App\Services\DeleteRequestService::bypass(true);

        try {
            if ($deletedBy && Schema::hasColumn($this->getTable(), 'deleted_by')) {
                $this->deleted_by = $deletedBy;
                $this->save();
            }

            $this->restoreInventoryQuantities();

            foreach (BikeMaintenanceItem::where('bike_maintenance_id', $this->id)->get() as $item) {
                $item->delete();
                $this->logCascade(
                    BikeMaintenanceItem::class,
                    $item->id,
                    $item->item_name ?: ('Item #'.$item->id),
                    'hasMany',
                    'maintenanceItems',
                    'Cascade deletion of bike maintenance item'
                );
            }

            $transactions = Transactions::withTrashed()
                ->withoutGlobalScope('branch')
                ->where('reference_type', 'Bike Maintenance')
                ->where('reference_id', $this->id)
                ->get();

            foreach ($transactions as $transaction) {
                if (! $transaction->trashed()) {
                    if ($deletedBy && Schema::hasColumn($transaction->getTable(), 'deleted_by')) {
                        $transaction->deleted_by = $deletedBy;
                        $transaction->save();
                    }
                    $transaction->delete();
                }

                $this->logCascade(
                    Transactions::class,
                    $transaction->id,
                    'Transaction #'.$transaction->id,
                    'hasMany',
                    'transactions',
                    'Cascade deletion of bike maintenance ledger'
                );
            }

            self::syncBikeMeterFieldsForBike($this->bike);
        } finally {
            if (! $wasBypassing) {
                \App\Services\DeleteRequestService::bypass(false);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function restoreRelatedRecords(): array
    {
        $restored = [];

        foreach (BikeMaintenanceItem::onlyTrashed()->where('bike_maintenance_id', $this->id)->get() as $item) {
            $item->restore();
            $restored[] = 'Item '.($item->item_name ?: '#'.$item->id);
        }

        $this->applyInventoryQuantities();

        $transactions = Transactions::onlyTrashed()
            ->withoutGlobalScope('branch')
            ->where('reference_type', 'Bike Maintenance')
            ->where('reference_id', $this->id)
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->restore();
            if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $transaction->deleted_by) {
                $transaction->deleted_by = null;
                $transaction->save();
            }
            $restored[] = 'Transaction #'.$transaction->id;
        }

        if (Schema::hasColumn($this->getTable(), 'deleted_by') && $this->deleted_by) {
            $this->deleted_by = null;
            $this->save();
        }

        self::syncBikeMeterFieldsForBike($this->bike);

        return $restored;
    }

    /**
     * Permanently remove related line items, ledger rows, and attachment.
     * Inventory was already returned on soft-delete.
     *
     * @return array<int, string>
     */
    public function purgeRelatedRecords(): array
    {
        $deleted = [];

        foreach (BikeMaintenanceItem::withTrashed()->where('bike_maintenance_id', $this->id)->get() as $item) {
            $deleted[] = 'Item '.($item->item_name ?: '#'.$item->id);
            $item->forceDelete();
        }

        $transactions = Transactions::withTrashed()
            ->withoutGlobalScope('branch')
            ->where('reference_type', 'Bike Maintenance')
            ->where('reference_id', $this->id)
            ->get();

        foreach ($transactions as $transaction) {
            $deleted[] = 'Transaction #'.$transaction->id;
            $transaction->forceDelete();
        }

        if ($this->attachment && Storage::disk('public')->exists($this->attachment)) {
            Storage::disk('public')->delete($this->attachment);
            $deleted[] = 'Attachment';
        }

        self::syncBikeMeterFieldsForBike($this->bike);

        return $deleted;
    }

    public function restoreInventoryQuantities(): void
    {
        $items = BikeMaintenanceItem::where('bike_maintenance_id', $this->id)
            ->whereNotNull('inventory_purchase_id')
            ->get();

        foreach ($items as $item) {
            $purchase = InventoryPurchase::find($item->inventory_purchase_id);
            if ($purchase) {
                $purchase->increment('remaining_quantity', $item->quantity);
            }
        }
    }

    public function applyInventoryQuantities(): void
    {
        $items = BikeMaintenanceItem::where('bike_maintenance_id', $this->id)
            ->whereNotNull('inventory_purchase_id')
            ->get();

        foreach ($items as $item) {
            $purchase = InventoryPurchase::find($item->inventory_purchase_id);
            if (! $purchase) {
                continue;
            }

            $qty = (float) $item->quantity;
            if ((float) $purchase->remaining_quantity < $qty) {
                throw new \RuntimeException(
                    'Cannot restore maintenance: insufficient stock for item '
                    .($item->item_name ?: '#'.$item->id)
                    .' (need '.$qty.', available '.$purchase->remaining_quantity.').'
                );
            }

            $purchase->decrement('remaining_quantity', $qty);
        }
    }

    protected function logCascade(
        string $relatedModel,
        $relatedId,
        string $relatedName,
        string $relationshipType,
        string $relationshipName,
        string $reason
    ): void {
        try {
            DeletionCascade::logCascade(
                self::class,
                $this->id,
                'MA-'.$this->id,
                $relatedModel,
                $relatedId,
                $relatedName,
                $relationshipType,
                $relationshipName,
                'soft',
                $reason
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to track bike maintenance cascade', [
                'maintenance_id' => $this->id,
                'related_model' => $relatedModel,
                'related_id' => $relatedId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
