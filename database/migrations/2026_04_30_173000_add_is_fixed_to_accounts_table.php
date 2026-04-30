<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            if (!Schema::hasColumn('accounts', 'is_fixed')) {
                $table->boolean('is_fixed')->default(false)->after('is_locked')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            if (Schema::hasColumn('accounts', 'is_fixed')) {
                $table->dropColumn('is_fixed');
            }
        });
    }
};
