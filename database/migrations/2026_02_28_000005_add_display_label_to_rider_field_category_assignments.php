<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_field_category_assignments', function (Blueprint $table) {
            $table->string('display_label', 255)->nullable()->after('field_key');
        });
    }

    public function down(): void
    {
        Schema::table('rider_field_category_assignments', function (Blueprint $table) {
            $table->dropColumn('display_label');
        });
    }
};
