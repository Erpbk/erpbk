<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'favorite_employee_ids')) {
                $table->json('favorite_employee_ids')->nullable()->after('favorite_rider_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'favorite_employee_ids')) {
                $table->dropColumn('favorite_employee_ids');
            }
        });
    }
};
