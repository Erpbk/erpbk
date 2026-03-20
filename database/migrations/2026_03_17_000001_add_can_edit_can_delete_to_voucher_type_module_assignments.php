<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_type_module_assignments', function (Blueprint $table) {
            $table->boolean('can_edit')->default(true)->after('module_key');
            $table->boolean('can_delete')->default(true)->after('can_edit');
        });
    }

    public function down(): void
    {
        Schema::table('voucher_type_module_assignments', function (Blueprint $table) {
            $table->dropColumn(['can_edit', 'can_delete']);
        });
    }
};
