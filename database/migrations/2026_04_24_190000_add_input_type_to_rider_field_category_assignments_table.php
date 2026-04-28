<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_field_category_assignments') || Schema::hasColumn('rider_field_category_assignments', 'input_type')) {
            return;
        }

        Schema::table('rider_field_category_assignments', function (Blueprint $table) {
            $table->string('input_type', 50)->nullable()->after('display_label');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rider_field_category_assignments') || !Schema::hasColumn('rider_field_category_assignments', 'input_type')) {
            return;
        }

        Schema::table('rider_field_category_assignments', function (Blueprint $table) {
            $table->dropColumn('input_type');
        });
    }
};
