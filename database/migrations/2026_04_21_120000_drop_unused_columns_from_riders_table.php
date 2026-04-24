<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'courier_id',
        'personal_contact',
        'company_contact',
        'NFDID',
        'cdm_deposit_id',
        'emirate_hub',
        'mashreq_id',
        'PID',
        'DEPT',
        'visa_status',
        'branded_plate_no',
        'vaccine_status',
        'attach_documents',
        'other_details',
        'VID',
        'visa_sponsor',
        'visa_occupation',
        'TAID',
        'passport_handover',
        'noon_no',
        'c3_card',
        'contract',
        'designation',
        'rider_status_option',
        'salary_model',
        'rider_reference',
        'job_status',
        'insurance',
        'insurance_expiry',
        'policy_no',
        'shift',
        'vat',
        'attendance_date',
        'absconder',
        'flowup',
        'l_license',
        'mol',
        'pro',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        $drop = array_values(array_filter($this->columns, fn ($column) => Schema::hasColumn('riders', $column)));
        if (empty($drop)) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) use ($drop) {
            $table->dropColumn($drop);
        });
    }

    public function down(): void
    {
        // Intentionally left empty: dropped legacy columns are not recreated automatically.
    }
};
