<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_invoice_items') || ! Schema::hasColumn('rider_invoice_items', 'qty')) {
            return;
        }

        DB::statement('ALTER TABLE `rider_invoice_items` MODIFY `qty` DECIMAL(15,2) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_invoice_items') || ! Schema::hasColumn('rider_invoice_items', 'qty')) {
            return;
        }

        DB::statement('ALTER TABLE `rider_invoice_items` MODIFY `qty` INT(11) NULL DEFAULT NULL');
    }
};
