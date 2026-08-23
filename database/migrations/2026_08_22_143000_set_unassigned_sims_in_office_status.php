<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sims')) {
            return;
        }

        DB::table('sims')
            ->whereNotNull('assign_to')
            ->where('status', '!=', 1)
            ->update(['status' => 1]);

        DB::table('sims')
            ->whereNull('assign_to')
            ->where('status', 0)
            ->update(['status' => 2]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('sims')) {
            return;
        }

        DB::table('sims')
            ->whereNull('assign_to')
            ->where('status', 2)
            ->update(['status' => 0]);
    }
};
