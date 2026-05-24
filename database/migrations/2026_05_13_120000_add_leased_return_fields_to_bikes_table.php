<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bikes')) {
            return;
        }
        Schema::table('bikes', function (Blueprint $table) {
            if (!Schema::hasColumn('bikes', 'leased_return_by')) {
                $table->date('leased_return_by')->nullable();
            }
            if (!Schema::hasColumn('bikes', 'leased_return_date')) {
                $table->date('leased_return_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bikes')) {
            return;
        }
        Schema::table('bikes', function (Blueprint $table) {
            if (Schema::hasColumn('bikes', 'leased_return_date')) {
                $table->dropColumn('leased_return_date');
            }
            if (Schema::hasColumn('bikes', 'leased_return_by')) {
                $table->dropColumn('leased_return_by');
            }
        });
    }
};
