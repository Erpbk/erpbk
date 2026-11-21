<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'company_contact')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('company_contact', 50)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'company_contact')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_contact');
        });
    }
};
