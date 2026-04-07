<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tenant DBs may not have `accounts` table yet when migrations are run.
        // Guard the alterations so tenant creation doesn't fail.
        if (!Schema::hasTable('accounts')) {
            return;
        }

        if (!Schema::hasColumn('accounts', 'deleted_at')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->softDeletes();
                $table->index('deleted_at'); // Add index for performance
            });
        }
        
        // Add deleted_by column if not exists
        if (!Schema::hasColumn('accounts', 'deleted_by')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            // Only attempt drop operations when columns exist.
            if (Schema::hasColumn('accounts', 'deleted_at')) {
                $table->dropSoftDeletes();
                $table->dropIndex(['deleted_at']); // Drop index on rollback
            }
        });
    }
};
