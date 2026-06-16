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

        if (! Schema::hasColumn('rider_inventory_assignments', 'customer_id')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->integer('customer_id')->nullable()->index()->after('inventory_item_id');
            });
        } else {
            DB::statement('ALTER TABLE `rider_inventory_assignments` MODIFY `customer_id` INT NULL');
        }

        if (! Schema::hasColumn('rider_inventory_assignments', 'returned_to_customer')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->boolean('returned_to_customer')->default(false)->index()->after('returned_by');
            });
        }

        $this->addCustomerForeignKey();
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_inventory_assignments')) {
            return;
        }

        $this->dropCustomerForeignKey();

        Schema::table('rider_inventory_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('rider_inventory_assignments', 'customer_id')) {
                $table->dropColumn('customer_id');
            }

            if (Schema::hasColumn('rider_inventory_assignments', 'returned_to_customer')) {
                $table->dropColumn('returned_to_customer');
            }
        });
    }

    private function addCustomerForeignKey(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('rider_inventory_assignments', 'customer_id')) {
            return;
        }

        $existing = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'rider_inventory_assignments'
               AND COLUMN_NAME = 'customer_id'
               AND REFERENCED_TABLE_NAME = 'customers'"
        );

        if ($existing) {
            return;
        }

        Schema::table('rider_inventory_assignments', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();
        });
    }

    private function dropCustomerForeignKey(): void
    {
        try {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        } catch (\Throwable $e) {
            // FK may not exist.
        }
    }
};
