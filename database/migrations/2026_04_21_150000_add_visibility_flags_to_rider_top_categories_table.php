<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_top_categories')) {
            return;
        }

        Schema::table('rider_top_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('rider_top_categories', 'show_in_top_bar')) {
                $table->boolean('show_in_top_bar')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('rider_top_categories', 'show_in_view_cards')) {
                $table->boolean('show_in_view_cards')->default(false)->after('show_in_top_bar');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rider_top_categories')) {
            return;
        }

        Schema::table('rider_top_categories', function (Blueprint $table) {
            if (Schema::hasColumn('rider_top_categories', 'show_in_top_bar')) {
                $table->dropColumn('show_in_top_bar');
            }
            if (Schema::hasColumn('rider_top_categories', 'show_in_view_cards')) {
                $table->dropColumn('show_in_view_cards');
            }
        });
    }
};
