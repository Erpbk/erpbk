<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rta_fines')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `rta_fines` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `trans_date` date DEFAULT NULL,
  `trans_code` bigint(20) DEFAULT NULL,
  `trip_date` date DEFAULT NULL,
  `trip_time` time DEFAULT NULL,
  `rider_id` bigint(20) DEFAULT NULL,
  `billing_month` date DEFAULT NULL,
  `ticket_no` varchar(50) DEFAULT NULL,
  `bike_id` bigint(20) DEFAULT NULL,
  `plate_no` varchar(50) DEFAULT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `service_charges` decimal(10,2) DEFAULT NULL,
  `admin_fee` decimal(10,2) DEFAULT NULL,
  `vat` decimal(10,2) DEFAULT NULL COMMENT 'Value Added Tax amount',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `rta_account_id` bigint(20) DEFAULT NULL,
  `debit_account_id` bigint(20) unsigned DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `pay_account` varchar(255) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rta_fines_rta_account_id_index` (`rta_account_id`),
  KEY `rta_fines_rider_id_index` (`rider_id`),
  KEY `rta_fines_bike_id_index` (`bike_id`),
  KEY `rta_fines_status_index` (`status`),
  KEY `rta_fines_trans_code_index` (`trans_code`),
  KEY `rta_fines_ticket_no_index` (`ticket_no`),
  KEY `rta_fines_deleted_at_index` (`deleted_at`),
  KEY `rta_fines_debit_account_id_index` (`debit_account_id`),
  KEY `rta_fines_branch_id_index` (`branch_id`),
  KEY `idx_rta_fines_company_id` (`company_id`),
  CONSTRAINT `fk_rta_fines_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rta_fines_rta_account_id_foreign` FOREIGN KEY (`rta_account_id`) REFERENCES `accounts` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=757 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rta_fines');
    }
};