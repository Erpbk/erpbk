<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_histories') && ! Schema::hasColumn('bike_histories', 'condition_attachments')) {
            Schema::table('bike_histories', function (Blueprint $table) {
                $table->json('condition_attachments')->nullable()->after('contract');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bike_histories') && Schema::hasColumn('bike_histories', 'condition_attachments')) {
            Schema::table('bike_histories', function (Blueprint $table) {
                $table->dropColumn('condition_attachments');
            });
        }
    }
};
