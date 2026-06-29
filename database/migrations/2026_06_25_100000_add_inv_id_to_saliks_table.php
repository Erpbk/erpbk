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
            if (!Schema::hasColumn('saliks', 'inv_id')) {
                $table->string('inv_id', 50)->nullable()->after('transaction_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('saliks')) {
            return;
        }

        Schema::table('saliks', function (Blueprint $table) {
            if (Schema::hasColumn('saliks', 'inv_id')) {
                $table->dropColumn('inv_id');
            }
        });
    }
};
