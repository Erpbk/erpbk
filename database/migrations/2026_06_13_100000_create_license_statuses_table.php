<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('license_statuses')) {
            return;
        }

        Schema::create('license_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->index();
            $table->string('description', 500)->nullable();
            $table->decimal('default_fee', 12, 2)->default(0);
            $table->enum('category', ['Document', 'Permit', 'License', 'Insurance', 'Other'])->default('Other');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('display_order')->default(1)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_statuses');
    }
};
