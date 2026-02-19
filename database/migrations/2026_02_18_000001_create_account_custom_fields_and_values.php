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
        Schema::create('account_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('data_type', 50); // text, textarea, number, decimal, date, datetime, dropdown, checkbox, email, url
            $table->boolean('is_mandatory')->default(false);
            $table->json('config')->nullable(); // type-specific options: max_length, options[], min, max, decimals, etc.
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        if (!Schema::hasColumn('accounts', 'custom_field_values')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->json('custom_field_values')->nullable()->after('is_locked');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_custom_fields');
        if (Schema::hasColumn('accounts', 'custom_field_values')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('custom_field_values');
            });
        }
    }
};
