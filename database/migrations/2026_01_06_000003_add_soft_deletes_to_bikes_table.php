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
        if (!Schema::hasColumn('bikes', 'deleted_at')) {
            Schema::table('bikes', function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('bikes', 'deleted_at')) {
            Schema::table('bikes', function (Blueprint $table) {
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }
    }
};
