<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('riders') || Schema::hasColumn('riders', 'rider_top_option_id')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            $table->foreignId('rider_top_option_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('rider_top_options')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('riders') || !Schema::hasColumn('riders', 'rider_top_option_id')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rider_top_option_id');
        });
    }
};

