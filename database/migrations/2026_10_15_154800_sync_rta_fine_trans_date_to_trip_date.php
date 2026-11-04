<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rta_fines')
            || ! Schema::hasColumn('rta_fines', 'trans_date')
            || ! Schema::hasColumn('rta_fines', 'trip_date')
        ) {
            return;
        }

        DB::table('rta_fines')
            ->whereNotNull('trip_date')
            ->update(['trans_date' => DB::raw('trip_date')]);

        if (! Schema::hasTable('transactions')
            || ! Schema::hasColumn('transactions', 'trans_date')
            || ! Schema::hasColumn('transactions', 'trans_code')
        ) {
            return;
        }

        // Accrual ledger lines share the fine's trans_code (payment lines use a different code).
        DB::statement('
            UPDATE transactions t
            INNER JOIN rta_fines f ON f.trans_code = t.trans_code
            SET t.trans_date = f.trans_date
            WHERE f.trip_date IS NOT NULL
              AND f.trans_code IS NOT NULL
        ');
    }

    public function down(): void
    {
        // Irreversible data sync — previous trans_date values are not retained.
    }
};
