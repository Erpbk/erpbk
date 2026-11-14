<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_location_profiles')) {
            return;
        }

        Schema::create('company_location_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('entity_type', 16)->default('br'); // ho | br | co
            $table->string('code', 32)->nullable();
            $table->string('label')->nullable();
            $table->string('place')->nullable();
            $table->json('vals')->nullable();
            $table->json('inh')->nullable();
            $table->json('banks')->nullable();
            $table->boolean('use_ho_bank')->default(false);
            $table->json('field_orders')->nullable();
            $table->json('docs_meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id'], 'clp_company_branch_unique');
            $table->index(['company_id', 'entity_type'], 'clp_company_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_location_profiles');
    }
};
