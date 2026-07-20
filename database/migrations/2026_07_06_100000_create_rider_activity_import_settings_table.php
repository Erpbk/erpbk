<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_activity_import_settings')) {
            return;
        }

        Schema::create('rider_activity_import_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedTinyInteger('header_rows_to_skip')->default(2);
            $table->json('column_mappings');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'customer_id'], 'rider_activity_import_company_customer_unique');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_activity_import_settings');
    }
};
