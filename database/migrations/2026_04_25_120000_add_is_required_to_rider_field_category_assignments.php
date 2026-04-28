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
        if (Schema::hasTable('rider_field_category_assignments') && !Schema::hasColumn('rider_field_category_assignments', 'is_required')) {
            Schema::table('rider_field_category_assignments', function (Blueprint $table) {
                $table->boolean('is_required')->default(false)->after('is_visible');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rider_field_category_assignments') && Schema::hasColumn('rider_field_category_assignments', 'is_required')) {
            Schema::table('rider_field_category_assignments', function (Blueprint $table) {
                $table->dropColumn('is_required');
            });
        }
    }
};
