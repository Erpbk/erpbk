<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('saliks')) {
            return;
        }

        Schema::table('saliks', function (Blueprint $table) {
            if (!Schema::hasColumn('saliks', 'salik_vat')) {
                $table->decimal('salik_vat', 10, 2)->nullable()->default(0)->after('amount');
            }
            if (!Schema::hasColumn('saliks', 'salik_vat_amount')) {
                $table->decimal('salik_vat_amount', 10, 2)->nullable()->default(0)->after('salik_vat');
            }
            if (!Schema::hasColumn('saliks', 'admin_vat')) {
                $table->decimal('admin_vat', 10, 2)->nullable()->default(0)->after('admin_charges');
            }
            if (!Schema::hasColumn('saliks', 'admin_vat_amount')) {
                $table->decimal('admin_vat_amount', 10, 2)->nullable()->default(0)->after('admin_vat');
            }
            if (!Schema::hasColumn('saliks', 'vat')) {
                $table->decimal('vat', 10, 2)->nullable()->default(0)->after('admin_vat_amount');
            }
            if (!Schema::hasColumn('saliks', 'rental_company_id')) {
                $table->unsignedBigInteger('rental_company_id')->nullable()->after('rider_id');
            }
            if (!Schema::hasColumn('saliks', 'payment_voucher_id')) {
                $table->unsignedBigInteger('payment_voucher_id')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('saliks')) {
            return;
        }

        Schema::table('saliks', function (Blueprint $table) {
            $columns = [
                'salik_vat',
                'salik_vat_amount',
                'admin_vat',
                'admin_vat_amount',
                'vat',
                'rental_company_id',
                'payment_voucher_id',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('saliks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
