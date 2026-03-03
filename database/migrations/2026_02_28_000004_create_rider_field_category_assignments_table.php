<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rider_field_category_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('field_key', 80)->unique()->comment('Rider table column name');
            $table->foreignId('category_id')->constrained('rider_categories')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        $slugMap = [
            'rider_info' => [
                'branch_id', 'name', 'rider_id', 'courier_id', 'personal_contact', 'company_contact',
                'personal_email', 'email', 'nationality', 'passport', 'passport_expiry', 'ethnicity', 'dob', 'image_name',
            ],
            'visa_info' => [
                'emirate_hub', 'emirate_id', 'emirate_exp', 'visa_status', 'passport_handover', 'visa_sponsor',
                'visa_occupation', 'license_no', 'license_expiry', 'road_permit', 'road_permit_expiry',
            ],
            'job_info' => [
                'VID', 'account_id', 'salary_model', 'fleet_supervisor', 'rider_reference', 'DEPT', 'PID',
                'job_status', 'customer_id', 'recruiter_id', 'recuriter', 'shift', 'attendance',
            ],
            'labor_info' => [
                'person_code', 'labor_card_number', 'labor_card_expiry', 'insurance', 'insurance_expiry',
                'policy_no', 'wps', 'c3_card', 'contract',
            ],
            'additional_info' => [
                'NFDID', 'cdm_deposit_id', 'mashreq_id', 'branded_plate_no', 'vaccine_status', 'absconder',
                'flowup', 'l_license', 'TAID', 'noon_no', 'vat', 'other_details',
            ],
        ];

        $slugToId = DB::table('rider_categories')->whereNotNull('slug')->pluck('id', 'slug')->all();
        $otherCategoryId = $slugToId['other'] ?? array_values($slugToId)[0] ?? null;
        $order = 0;
        $now = now();

        foreach ($slugMap as $slug => $keys) {
            $categoryId = $slugToId[$slug] ?? $otherCategoryId;
            if ($categoryId === null) {
                continue;
            }
            foreach ($keys as $fieldKey) {
                DB::table('rider_field_category_assignments')->insert([
                    'field_key' => $fieldKey,
                    'category_id' => $categoryId,
                    'display_order' => $order++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_field_category_assignments');
    }
};
