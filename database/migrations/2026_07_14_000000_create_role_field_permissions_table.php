<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalized field-level permissions per role and module.
 * One row per (role, module, field): stores visible/editable/required flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_field_permissions')) {
            return;
        }

        Schema::create('role_field_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->index();
            $table->unsignedBigInteger('module_id')->index();
            $table->string('field_name', 191);
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->boolean('visible')->default(true);
            $table->boolean('editable')->default(true);
            $table->boolean('required')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'module_id', 'field_name'], 'role_field_permissions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_field_permissions');
    }
};
