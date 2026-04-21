<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `vouchers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `trans_date` date DEFAULT NULL,
  `trans_code` bigint(20) DEFAULT NULL,
  `posting_date` date DEFAULT NULL,
  `billing_month` date DEFAULT NULL,
  `payment_to` bigint(20) DEFAULT NULL,
  `payment_from` bigint(20) DEFAULT NULL,
  `payment_type` bigint(20) DEFAULT NULL,
  `voucher_type` varchar(20) DEFAULT '1',
  `reason` varchar(255) DEFAULT NULL,
  `amount` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `ref_id` bigint(20) DEFAULT NULL,
  `rider_id` bigint(20) DEFAULT NULL,
  `vendor_id` bigint(20) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `custom_field_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_field_values`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `invoice_voucher_type` tinyint(4) DEFAULT NULL,
  `Created_By` int(11) DEFAULT NULL,
  `toll_gate` varchar(50) DEFAULT NULL,
  `trip_date` datetime DEFAULT NULL,
  `direction` varchar(255) DEFAULT NULL,
  `lease_company` bigint(20) DEFAULT NULL,
  `Updated_By` int(11) DEFAULT NULL,
  `attach_file` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vouchers_ref_id_index` (`ref_id`),
  KEY `vouchers_voucher_type_index` (`voucher_type`),
  KEY `vouchers_trans_code_index` (`trans_code`),
  KEY `vouchers_deleted_at_index` (`deleted_at`),
  KEY `vouchers_ref_id_type_index` (`ref_id`,`voucher_type`),
  KEY `vouchers_lease_company_index` (`lease_company`),
  KEY `idx_vouchers_company_id` (`company_id`),
  CONSTRAINT `fk_vouchers_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21353 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};