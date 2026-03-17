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
        if (Schema::hasTable('rider_field_category_assignments') && !Schema::hasColumn('rider_field_category_assignments', 'is_visible')) {
            Schema::table('rider_field_category_assignments', function (Blueprint $table) {
                $table->boolean('is_visible')->default(true)->after('display_order')
                    ->comment('When false, field is hidden from Rider Add/Edit/View');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rider_field_category_assignments') && Schema::hasColumn('rider_field_category_assignments', 'is_visible')) {
            Schema::table('rider_field_category_assignments', function (Blueprint $table) {
                $table->dropColumn('is_visible');
            });
        }
    }
};
