<?php

use App\Http\Controllers\BikeMaintenanceController;
use App\Models\BikeMaintenance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Rebuild existing Bike Maintenance (MA-) ledger postings under the new rules:
 * - Internal + Company: cost only, VAT 0
 * - External/leasing + User: recover gross incl. VAT
 * - External/leasing + Company: Dr maint (ex-VAT) + Dr VAT purchase
 *
 * Also normalizes stored internal+company line items before posting.
 * Irreversible without a DB backup (down is a no-op).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bike_maintenances') || ! Schema::hasTable('transactions')) {
            return;
        }

        /** @var BikeMaintenanceController $controller */
        $controller = app(BikeMaintenanceController::class);

        BikeMaintenance::query()
            ->orderBy('id')
            ->chunkById(50, function ($maintenances) use ($controller) {
                foreach ($maintenances as $maintenance) {
                    DB::beginTransaction();
                    try {
                        $result = $controller->rebuildLedgerForMaintenance($maintenance);
                        if (! $result['ok']) {
                            DB::rollBack();
                            Log::warning('Bike maintenance ledger rebuild skipped', [
                                'bike_maintenance_id' => $maintenance->id,
                                'missing' => $result['missing'],
                            ]);
                            continue;
                        }
                        DB::commit();
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Log::error('Bike maintenance ledger rebuild failed', [
                            'bike_maintenance_id' => $maintenance->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Prior ledger lines are force-deleted; cannot restore without a backup.
    }
};
