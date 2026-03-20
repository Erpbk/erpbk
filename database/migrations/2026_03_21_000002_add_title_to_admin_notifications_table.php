<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_notifications')) {
            return;
        }
        if (Schema::hasColumn('admin_notifications', 'title')) {
            return;
        }

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->string('title')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_notifications') || !Schema::hasColumn('admin_notifications', 'title')) {
            return;
        }

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
