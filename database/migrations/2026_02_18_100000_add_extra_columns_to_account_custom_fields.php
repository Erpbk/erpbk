<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_custom_fields', function (Blueprint $table) {
            $table->text('help_text')->nullable()->after('label');
            $table->json('data_privacy')->nullable()->after('help_text'); // ['pii' => bool, 'ephi' => bool]
            $table->boolean('prevent_duplicate_values')->default(false)->after('data_privacy');
            $table->string('default_value', 500)->nullable()->after('prevent_duplicate_values');
            $table->string('input_format', 100)->nullable()->after('default_value');
        });
    }

    public function down(): void
    {
        Schema::table('account_custom_fields', function (Blueprint $table) {
            $table->dropColumn(['help_text', 'data_privacy', 'prevent_duplicate_values', 'default_value', 'input_format']);
        });
    }
};
