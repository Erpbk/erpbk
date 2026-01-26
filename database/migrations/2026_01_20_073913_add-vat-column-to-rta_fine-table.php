<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rta_fines', 'vat')) {
            Schema::table('rta_fines', function (Blueprint $table) {
                $table->decimal('vat', 10, 2)
                    ->nullable()
                    ->after('admin_fee')
                    ->comment('Value Added Tax amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('rta_fines', 'vat')) {
            Schema::table('rta_fines', function (Blueprint $table) {
                $table->dropColumn('vat');
            });
        }
    }
};
