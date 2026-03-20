<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_type_module_assignments')) {
            return;
        }

        if (Schema::hasColumn('voucher_type_module_assignments', 'can_edit')) {
            return;
        }
        Schema::table('voucher_type_module_assignments', function (Blueprint $table) {
            $table->boolean('can_edit')->default(true)->after('module_key');
            $table->boolean('can_delete')->default(true)->after('can_edit');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('voucher_type_module_assignments')) {
            return;
        }

        if (!Schema::hasColumn('voucher_type_module_assignments', 'can_edit') && !Schema::hasColumn('voucher_type_module_assignments', 'can_delete')) {
            return;
        }
        Schema::table('voucher_type_module_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_type_module_assignments', 'can_edit')) {
                $table->dropColumn('can_edit');
            }
            if (Schema::hasColumn('voucher_type_module_assignments', 'can_delete')) {
                $table->dropColumn('can_delete');
            }
        });
    }
};
