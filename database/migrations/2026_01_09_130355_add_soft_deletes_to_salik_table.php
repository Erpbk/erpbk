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
        // Check if table exists and determine correct table name
        $tableName = 'saliks'; // Laravel pluralizes by default
        if (!Schema::hasTable($tableName)) {
            $tableName = 'salik'; // Try singular if plural doesn't exist
        }

        if (Schema::hasTable($tableName)) {
            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                    $table->index('deleted_at'); // Add index for performance
                });
            }

            // Add deleted_by column if not exists
            if (!Schema::hasColumn($tableName, 'deleted_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if table exists and determine correct table name
        $tableName = 'saliks'; // Laravel pluralizes by default
        if (!Schema::hasTable($tableName)) {
            $tableName = 'salik'; // Try singular if plural doesn't exist
        }

        if (Schema::hasTable($tableName)) {
            if (Schema::hasColumn($tableName, 'deleted_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('deleted_by');
                });
            }

            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['deleted_at']); // Drop index on rollback
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
