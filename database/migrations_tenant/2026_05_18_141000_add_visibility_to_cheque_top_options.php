<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cheque_top_options')) {
            return;
        }

        Schema::table('cheque_top_options', function (Blueprint $table) {
            if (!Schema::hasColumn('cheque_top_options', 'show_in_top_bar')) {
                $table->boolean('show_in_top_bar')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('cheque_top_options', 'show_in_view_cards')) {
                $table->boolean('show_in_view_cards')->default(true)->after('show_in_top_bar');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cheque_top_options')) {
            return;
        }

        Schema::table('cheque_top_options', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('cheque_top_options', 'show_in_top_bar')) {
                $drop[] = 'show_in_top_bar';
            }
            if (Schema::hasColumn('cheque_top_options', 'show_in_view_cards')) {
                $drop[] = 'show_in_view_cards';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
