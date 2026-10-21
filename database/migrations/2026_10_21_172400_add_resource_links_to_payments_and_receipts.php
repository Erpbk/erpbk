<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'resource_links')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->json('resource_links')->nullable()->after('reference');
            });
        }

        if (Schema::hasTable('receipts') && ! Schema::hasColumn('receipts', 'resource_links')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->json('resource_links')->nullable()->after('reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'resource_links')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('resource_links');
            });
        }

        if (Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'resource_links')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->dropColumn('resource_links');
            });
        }
    }
};
