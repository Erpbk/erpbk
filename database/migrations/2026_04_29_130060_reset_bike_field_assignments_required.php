<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bike_field_category_assignments')) {
            return;
        }

        // If bike settings doesn't explicitly mark a fixed field as required,
        // we should not force validation errors.
        if (Schema::hasColumn('bike_field_category_assignments', 'is_required')) {
            DB::table('bike_field_category_assignments')->update(['is_required' => false, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op: previous required defaults are unknown/tenant-specific.
    }
};

