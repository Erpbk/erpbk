<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('riders')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `riders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `rider_id` varchar(255) DEFAULT NULL,
  `courier_id` varchar(255) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `personal_contact` varchar(191) DEFAULT NULL,
  `company_contact` varchar(191) DEFAULT NULL,
  `personal_email` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `nationality` bigint(20) unsigned DEFAULT NULL,
  `NFDID` varchar(191) DEFAULT NULL,
  `cdm_deposit_id` varchar(191) DEFAULT NULL,
  `doj` date DEFAULT NULL COMMENT '\r\n',
  `emirate_hub` varchar(191) DEFAULT NULL,
  `emirate_id` varchar(191) DEFAULT NULL,
  `emirate_exp` date DEFAULT NULL COMMENT 'emirated id expirty',
  `mashreq_id` varchar(191) DEFAULT NULL,
  `passport` varchar(191) DEFAULT NULL,
  `passport_expiry` date DEFAULT NULL,
  `PID` bigint(20) unsigned DEFAULT NULL,
  `DEPT` varchar(191) DEFAULT NULL,
  `ethnicity` varchar(191) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `license_no` varchar(191) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `road_permit` varchar(255) DEFAULT NULL,
  `road_permit_expiry` date DEFAULT NULL,
  `visa_status` varchar(191) DEFAULT NULL,
  `branded_plate_no` varchar(191) DEFAULT NULL,
  `vaccine_status` enum('0','1') DEFAULT '0',
  `attach_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `other_details` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `custom_field_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_field_values`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `VID` int(11) DEFAULT NULL,
  `visa_sponsor` varchar(100) DEFAULT NULL,
  `visa_occupation` varchar(100) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `TAID` bigint(20) DEFAULT NULL,
  `fleet_supervisor` varchar(50) DEFAULT NULL,
  `passport_handover` varchar(50) DEFAULT NULL,
  `noon_no` varchar(100) DEFAULT NULL,
  `wps` varchar(100) DEFAULT NULL,
  `c3_card` varchar(100) DEFAULT NULL,
  `contract` varchar(100) DEFAULT NULL,
  `designation` varchar(50) DEFAULT '',
  `rider_status_option` varchar(50) DEFAULT NULL,
  `image_name` varchar(255) DEFAULT NULL,
  `salary_model` varchar(100) DEFAULT NULL,
  `rider_reference` varchar(255) DEFAULT NULL,
  `job_status` tinyint(4) DEFAULT 1,
  `person_code` varchar(50) DEFAULT NULL,
  `labor_card_number` varchar(100) DEFAULT NULL,
  `labor_card_expiry` date DEFAULT NULL,
  `insurance` varchar(100) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `policy_no` varchar(255) DEFAULT NULL,
  `shift` varchar(100) DEFAULT NULL,
  `attendance` varchar(50) DEFAULT NULL,
  `vat` tinyint(4) DEFAULT 2,
  `attendance_date` date DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `absconder` int(11) DEFAULT NULL,
  `flowup` int(11) DEFAULT NULL,
  `l_license` int(11) DEFAULT NULL,
  `recuriter` varchar(255) DEFAULT NULL,
  `recruiter_id` bigint(20) DEFAULT NULL,
  `mol` int(11) DEFAULT NULL,
  `pro` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rider_id` (`rider_id`),
  KEY `idx_riders_account_id` (`account_id`),
  KEY `idx_riders_status` (`status`),
  KEY `idx_riders_company_id` (`company_id`),
  CONSTRAINT `fk_riders_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1803 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};