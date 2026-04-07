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
        if (Schema::hasTable('leasing_companies')) {
            return;
        }

        Schema::create('leasing_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_number', 100)->nullable();
            $table->text('detail')->nullable();

            // Used by LeasingCompanies model and later FK migrations.
            $table->unsignedBigInteger('account_id')->nullable();

            // 1=Active/visible, 0=Inactive. Matches controller queries.
            $table->tinyInteger('status')->default(1);

            $table->timestamps();

            // Soft deletes expected by LeasingCompanies model.
            $table->softDeletes();
            // MariaDB doesn't support `after(...)` modifiers inside Schema::create().
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Helpful for later migrations and common queries.
            $table->index('account_id', 'leasing_companies_account_id_index');
            $table->index('status', 'leasing_companies_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leasing_companies');
    }
};

