<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_stamp_signatures')) {
            return;
        }

        Schema::create('user_stamp_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('stamp_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('designation')->nullable();
            $table->json('doc_matrix')->nullable();
            $table->json('other_docs')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id'], 'uss_company_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stamp_signatures');
    }
};
