<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saved layouts for the rider report predate its current column set, so their
     * keys no longer exist. Applying them hides almost every column, and the panel
     * only falls back to defaults when nothing at all matches. Drop them so the
     * report starts from its defaults.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_table_settings')) {
            return;
        }

        DB::table('user_table_settings')
            ->where('table_identifier', 'rider_report_table')
            ->where(function ($query) {
                $query->whereNull('column_order')
                    ->orWhere('column_order', 'not like', '%total_amount%');
            })
            ->delete();
    }

    public function down(): void
    {
        // Discarded layouts cannot be restored.
    }
};
