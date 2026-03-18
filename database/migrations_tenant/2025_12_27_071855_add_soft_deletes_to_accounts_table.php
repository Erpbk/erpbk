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
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['deleted_at']); // Drop index on rollback
        });
    }
};
