<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vat_returns')) {
            return;
        }

        // Normalize legacy string statuses before changing column type.
        if (Schema::hasColumn('vat_returns', 'status')) {
            DB::table('vat_returns')->where('status', 'unpaid')->update(['status' => '0']);
            DB::table('vat_returns')->where('status', 'paid')->update(['status' => '1']);
            DB::table('vat_returns')->where('status', 'partially_paid')->update(['status' => '3']);
            DB::table('vat_returns')->whereNull('status')->update(['status' => '0']);
        }

        Schema::table('vat_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('vat_returns', 'partial_paid_amount')) {
                $table->json('partial_paid_amount')->nullable()->after('status');
            }
            if (! Schema::hasColumn('vat_returns', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('vat_returns', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            }
        });

        // status: unpaid=0, paid=1, partially_paid=3
        if (Schema::hasColumn('vat_returns', 'status')) {
            $driver = Schema::getConnection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE vat_returns MODIFY status TINYINT NOT NULL DEFAULT 0');
            } else {
                Schema::table('vat_returns', function (Blueprint $table) {
                    $table->unsignedTinyInteger('status')->default(0)->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vat_returns')) {
            return;
        }

        if (Schema::hasColumn('vat_returns', 'status')) {
            $driver = Schema::getConnection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE vat_returns MODIFY status VARCHAR(20) NOT NULL DEFAULT 'unpaid'");
            }
            DB::table('vat_returns')->where('status', 0)->update(['status' => 'unpaid']);
            DB::table('vat_returns')->where('status', 1)->update(['status' => 'paid']);
            DB::table('vat_returns')->where('status', 3)->update(['status' => 'partially_paid']);
        }

        Schema::table('vat_returns', function (Blueprint $table) {
            if (Schema::hasColumn('vat_returns', 'partial_paid_amount')) {
                $table->dropColumn('partial_paid_amount');
            }
            if (Schema::hasColumn('vat_returns', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('vat_returns', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
