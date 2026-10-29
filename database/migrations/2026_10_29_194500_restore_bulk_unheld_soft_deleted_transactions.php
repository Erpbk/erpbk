<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Re-soft-delete transactions that were wrongly restored by an unscoped
 * deleted_at clear on 2026-10-29 11:12:11 (voucher-approval "un-hold" mistake).
 *
 * Source of truth: overnight GZ dump values in
 * database/data/2026_10_29_restore_soft_deleted_transactions.json
 *
 * Guards: only updates rows that are still active with the incident fingerprint
 * (deleted_at IS NULL, updated_at = incident timestamp). Skips already-fixed rows.
 *
 * Irreversible without a backup if down() is used carelessly — down() only
 * clears deleted_at again for the same IDs when they still match restored values.
 */
return new class extends Migration
{
    private const DATA_FILE = 'database/data/2026_10_29_restore_soft_deleted_transactions.json';

    private const INCIDENT_UPDATED_AT = '2026-10-29 11:12:11';

    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        $payload = $this->loadPayload();
        $incidentAt = $payload['incident_updated_at'] ?? self::INCIDENT_UPDATED_AT;
        $rows = $payload['rows'] ?? [];

        if ($rows === []) {
            Log::warning('restore_bulk_unheld_soft_deleted_transactions: empty data file');

            return;
        }

        $before = (int) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('updated_at', $incidentAt)
            ->count();

        $updated = 0;
        $skipped = 0;

        foreach (array_chunk($rows, 200) as $chunk) {
            foreach ($chunk as $row) {
                $id = (int) ($row['id'] ?? 0);
                $deletedAt = $row['deleted_at'] ?? null;
                $updatedAt = $row['updated_at'] ?? null;

                if ($id <= 0 || $deletedAt === null || $deletedAt === '') {
                    $skipped++;
                    continue;
                }

                $affected = DB::table('transactions')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->where('updated_at', $incidentAt)
                    ->update([
                        'deleted_at' => $deletedAt,
                        'updated_at' => $updatedAt ?: $deletedAt,
                    ]);

                $updated += (int) $affected;
                if ($affected === 0) {
                    $skipped++;
                }
            }
        }

        $after = (int) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('updated_at', $incidentAt)
            ->count();

        Log::info('restore_bulk_unheld_soft_deleted_transactions finished', [
            'fingerprint_before' => $before,
            'fingerprint_after' => $after,
            'rows_in_file' => count($rows),
            'rows_updated' => $updated,
            'rows_skipped' => $skipped,
        ]);

        // Surface counts in migrate output when possible.
        if (app()->runningInConsole()) {
            echo "Soft-delete restore: fingerprint before={$before}, updated={$updated}, fingerprint after={$after}\n";
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        $payload = $this->loadPayload();
        $rows = $payload['rows'] ?? [];
        $incidentAt = $payload['incident_updated_at'] ?? self::INCIDENT_UPDATED_AT;

        foreach (array_chunk($rows, 200) as $chunk) {
            foreach ($chunk as $row) {
                $id = (int) ($row['id'] ?? 0);
                $deletedAt = $row['deleted_at'] ?? null;
                if ($id <= 0 || $deletedAt === null) {
                    continue;
                }

                // Only undo rows we likely restored (still soft-deleted with original deleted_at).
                DB::table('transactions')
                    ->where('id', $id)
                    ->where('deleted_at', $deletedAt)
                    ->update([
                        'deleted_at' => null,
                        'updated_at' => $incidentAt,
                    ]);
            }
        }
    }

    /**
     * @return array{incident_updated_at?: string, rows?: list<array{id:int, deleted_at:?string, updated_at:?string}>}
     */
    private function loadPayload(): array
    {
        $path = base_path(self::DATA_FILE);
        if (! is_file($path)) {
            throw new RuntimeException('Missing restore data file: '.$path);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid restore data JSON: '.$path);
        }

        return $decoded;
    }
};
