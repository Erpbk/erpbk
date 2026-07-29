<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_invoices')) {
            return;
        }

        $hasFrom = Schema::hasColumn('rider_invoices', 'service_period_from');
        $hasTo = Schema::hasColumn('rider_invoices', 'service_period_to');

        Schema::table('rider_invoices', function (Blueprint $table) use ($hasFrom, $hasTo) {
            if (! $hasFrom) {
                $table->date('service_period_from')->nullable()->after('inv_date');
            }

            if (! $hasTo) {
                $table->date('service_period_to')->nullable()->after('service_period_from');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_invoices')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('rider_invoices', 'service_period_from') ? 'service_period_from' : null,
            Schema::hasColumn('rider_invoices', 'service_period_to') ? 'service_period_to' : null,
        ]));

        Schema::table('rider_invoices', function (Blueprint $table) use ($columns) {
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
