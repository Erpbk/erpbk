<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('garages', 'garage_type')) {
            Schema::table('garages', function (Blueprint $table) {
                $table->string('garage_type', 20)->default('external')->after('branch_id');
            });
        }
        DB::table('garages')->whereNull('garage_type')->update(['garage_type' => 'external']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('garages', 'garage_type')) {
            Schema::table('garages', function (Blueprint $table) {
                $table->dropColumn('garage_type');
            });
        }
    }
};
