<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collapse multi-module agreement assignments to a single module (first key).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agreement_categories') || ! Schema::hasColumn('agreement_categories', 'assigned_modules')) {
            return;
        }

        $rows = DB::table('agreement_categories')->select('id', 'assigned_modules')->get();
        foreach ($rows as $row) {
            $modules = json_decode((string) $row->assigned_modules, true);
            if (! is_array($modules) || count($modules) <= 1) {
                continue;
            }

            $first = null;
            foreach ($modules as $key) {
                if (is_string($key) && trim($key) !== '') {
                    $first = trim($key);
                    break;
                }
            }

            DB::table('agreement_categories')->where('id', $row->id)->update([
                'assigned_modules' => json_encode($first !== null ? [$first] : []),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible collapse of multi-module assignments.
    }
};
