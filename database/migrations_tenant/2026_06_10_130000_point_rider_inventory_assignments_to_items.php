<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_inventory_assignments') || ! Schema::hasTable('items')) {
            return;
        }

        $this->dropInventoryItemForeignKey();

        if (Schema::hasTable('rider_inventory_items')) {
            $this->remapAssignmentsToItems();
        }

        $this->addItemsForeignKey();
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_inventory_assignments') || ! Schema::hasTable('rider_inventory_items')) {
            return;
        }

        $this->dropInventoryItemForeignKey();

        Schema::table('rider_inventory_assignments', function (Blueprint $table) {
            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('rider_inventory_items')
                ->restrictOnDelete();
        });
    }

    private function dropInventoryItemForeignKey(): void
    {
        try {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->dropForeign(['inventory_item_id']);
            });
        } catch (\Throwable $e) {
            // FK may already have been dropped by a failed prior run.
        }
    }

    private function remapAssignmentsToItems(): void
    {
        $idMap = [];

        foreach (DB::table('rider_inventory_items')->get() as $riderItem) {
            $idMap[(int) $riderItem->id] = $this->resolveItemIdForRiderInventoryItem($riderItem);
        }

        foreach (DB::table('rider_inventory_assignments')->select('id', 'inventory_item_id')->get() as $assignment) {
            $currentId = (int) $assignment->inventory_item_id;

            if (DB::table('items')->where('id', $currentId)->exists()) {
                continue;
            }

            if (! isset($idMap[$currentId])) {
                continue;
            }

            DB::table('rider_inventory_assignments')
                ->where('id', $assignment->id)
                ->update(['inventory_item_id' => $idMap[$currentId]]);
        }
    }

    private function resolveItemIdForRiderInventoryItem(object $riderItem): int
    {
        $query = DB::table('items')->where('name', $riderItem->name);

        if (Schema::hasColumn('items', 'company_id') && ! empty($riderItem->company_id)) {
            $query->where('company_id', $riderItem->company_id);
        }

        $existing = $query->first();

        if ($existing) {
            $this->ensureRiderInventoryOwner((int) $existing->id, $existing->owner ?? null);

            return (int) $existing->id;
        }

        $now = now();
        $data = [
            'name' => $riderItem->name,
            'price' => $riderItem->item_price,
            'cost' => $riderItem->item_price,
            'owner' => json_encode(['riderInventory']),
            'status' => ! empty($riderItem->is_active) ? 1 : 2,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('items', 'company_id')) {
            $data['company_id'] = $riderItem->company_id;
        }

        if (Schema::hasColumn('items', 'created_by')) {
            $data['created_by'] = $riderItem->created_by;
        }

        return (int) DB::table('items')->insertGetId($data);
    }

    private function ensureRiderInventoryOwner(int $itemId, ?string $owner): void
    {
        $owners = [];

        if ($owner) {
            $decoded = json_decode($owner, true);
            if (is_array($decoded)) {
                $owners = $decoded;
            }
        }

        if (in_array('riderInventory', $owners, true)) {
            return;
        }

        $owners[] = 'riderInventory';

        DB::table('items')->where('id', $itemId)->update([
            'owner' => json_encode($owners),
            'updated_at' => now(),
        ]);
    }

    private function addItemsForeignKey(): void
    {
        Schema::table('rider_inventory_assignments', function (Blueprint $table) {
            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('items')
                ->restrictOnDelete();
        });
    }
};
