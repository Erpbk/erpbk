<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_inventory_assignments')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `rider_inventory_assignments` MODIFY `status` ENUM('assigned','returned','lost','returned_to_customer') NOT NULL DEFAULT 'assigned'"
        );

        if (! Schema::hasColumn('rider_inventory_assignments', 'returned_to_customer')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->date('returned_to_customer')->nullable()->after('returned_by');
            });

            return;
        }

        DB::table('rider_inventory_assignments')
            ->where('returned_to_customer', 1)
            ->update(['status' => 'returned_to_customer']);

        Schema::table('rider_inventory_assignments', function (Blueprint $table) {
            $table->date('returned_to_customer_date_tmp')->nullable()->after('returned_by');
        });

        DB::statement(
            "UPDATE `rider_inventory_assignments`
             SET `returned_to_customer_date_tmp` = COALESCE(`return_date`, CURDATE())
             WHERE `status` = 'returned_to_customer'"
        );

        Schema::table('rider_inventory_assignments', function (Blueprint $table) {
            $table->dropColumn('returned_to_customer');
        });

        DB::statement(
            'ALTER TABLE `rider_inventory_assignments` CHANGE `returned_to_customer_date_tmp` `returned_to_customer` DATE NULL'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_inventory_assignments')) {
            return;
        }

        if (Schema::hasColumn('rider_inventory_assignments', 'returned_to_customer')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->boolean('returned_to_customer_flag_tmp')->default(false)->after('returned_by');
            });

            DB::statement(
                "UPDATE `rider_inventory_assignments`
                 SET `returned_to_customer_flag_tmp` = 1
                 WHERE `status` = 'returned_to_customer'"
            );

            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->dropColumn('returned_to_customer');
            });

            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->boolean('returned_to_customer')->default(false)->after('returned_by');
            });

            DB::statement(
                'UPDATE `rider_inventory_assignments` SET `returned_to_customer` = `returned_to_customer_flag_tmp`'
            );

            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->dropColumn('returned_to_customer_flag_tmp');
            });
        }

        DB::table('rider_inventory_assignments')
            ->where('status', 'returned_to_customer')
            ->update(['status' => 'returned']);

        DB::statement(
            "ALTER TABLE `rider_inventory_assignments` MODIFY `status` ENUM('assigned','returned','lost') NOT NULL DEFAULT 'assigned'"
        );
    }
};
