<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fixed_assets') || !Schema::hasColumn('fixed_assets', 'acquisition_type')) {
            return;
        }

        DB::table('fixed_assets')
            ->where('acquisition_type', 'already_owned')
            ->update(['acquisition_type' => 'opening_balance']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('fixed_assets') || !Schema::hasColumn('fixed_assets', 'acquisition_type')) {
            return;
        }

        DB::table('fixed_assets')
            ->where('acquisition_type', 'opening_balance')
            ->update(['acquisition_type' => 'already_owned']);
    }
};
