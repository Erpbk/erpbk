<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agreement_categories')) {
            Schema::create('agreement_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('group_key', 80)->comment('Tab group e.g. rider_agreements');
                $table->string('slug', 80);
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'slug']);
                $table->index(['company_id', 'group_key', 'status']);
            });
        }

        if (!Schema::hasTable('agreement_templates')) {
            Schema::create('agreement_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('category_id');
                $table->string('template_name');
                $table->string('template_type', 30)->default('corporate')->comment('corporate|premium PDF layout');
                $table->longText('description')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->index(['company_id', 'category_id', 'status']);
                $table->foreign('category_id')->references('id')->on('agreement_categories')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('agreement_placeholders')) {
            Schema::create('agreement_placeholders', function (Blueprint $table) {
                $table->id();
                $table->string('placeholder', 80)->unique();
                $table->string('description')->nullable();
                $table->string('group_label', 80)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $this->seedPlaceholders();
    }

    private function seedPlaceholders(): void
    {
        $rows = [
            ['placeholder' => '{rider_name}', 'description' => 'Rider full name', 'group_label' => 'Personal Information', 'sort_order' => 1],
            ['placeholder' => '{rider_code}', 'description' => 'Rider ID / code', 'group_label' => 'Personal Information', 'sort_order' => 2],
            ['placeholder' => '{rider_email}', 'description' => 'Email address', 'group_label' => 'Personal Information', 'sort_order' => 3],
            ['placeholder' => '{rider_phone}', 'description' => 'Phone number', 'group_label' => 'Personal Information', 'sort_order' => 4],
            ['placeholder' => '{rider_cnic}', 'description' => 'CNIC / national ID', 'group_label' => 'Personal Information', 'sort_order' => 5],
            ['placeholder' => '{rider_passport_number}', 'description' => 'Passport number', 'group_label' => 'Personal Information', 'sort_order' => 6],
            ['placeholder' => '{rider_nationality}', 'description' => 'Nationality', 'group_label' => 'Personal Information', 'sort_order' => 7],
            ['placeholder' => '{rider_date_of_birth}', 'description' => 'Date of birth', 'group_label' => 'Personal Information', 'sort_order' => 8],
            ['placeholder' => '{rider_gender}', 'description' => 'Gender', 'group_label' => 'Personal Information', 'sort_order' => 9],
            ['placeholder' => '{rider_address}', 'description' => 'Address', 'group_label' => 'Address Information', 'sort_order' => 10],
            ['placeholder' => '{rider_city}', 'description' => 'City', 'group_label' => 'Address Information', 'sort_order' => 11],
            ['placeholder' => '{rider_country}', 'description' => 'Country', 'group_label' => 'Address Information', 'sort_order' => 12],
            ['placeholder' => '{joining_date}', 'description' => 'Date of joining', 'group_label' => 'Employment Information', 'sort_order' => 13],
            ['placeholder' => '{designation}', 'description' => 'Job designation', 'group_label' => 'Employment Information', 'sort_order' => 14],
            ['placeholder' => '{salary}', 'description' => 'Salary', 'group_label' => 'Employment Information', 'sort_order' => 15],
            ['placeholder' => '{branch_name}', 'description' => 'Branch name', 'group_label' => 'Employment Information', 'sort_order' => 16],
            ['placeholder' => '{company_name}', 'description' => 'Company name', 'group_label' => 'Employment Information', 'sort_order' => 17],
            ['placeholder' => '{bike_number}', 'description' => 'Assigned bike plate / number', 'group_label' => 'Vehicle Information', 'sort_order' => 18],
            ['placeholder' => '{bike_model}', 'description' => 'Bike model', 'group_label' => 'Vehicle Information', 'sort_order' => 19],
            ['placeholder' => '{current_date}', 'description' => 'Today\'s date', 'group_label' => 'System Information', 'sort_order' => 20],
            ['placeholder' => '{agreement_date}', 'description' => 'Agreement date', 'group_label' => 'System Information', 'sort_order' => 21],
        ];

        $now = now();
        foreach ($rows as $row) {
            DB::table('agreement_placeholders')->updateOrInsert(
                ['placeholder' => $row['placeholder']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_templates');
        Schema::dropIfExists('agreement_categories');
        Schema::dropIfExists('agreement_placeholders');
    }
};
