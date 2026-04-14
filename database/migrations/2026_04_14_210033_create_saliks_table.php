<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('saliks')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `saliks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `trip_date` varchar(255) DEFAULT NULL,
  `trip_time` varchar(255) DEFAULT NULL,
  `billing_month` varchar(255) DEFAULT NULL,
  `transaction_post_date` varchar(255) DEFAULT NULL,
  `toll_gate` varchar(255) DEFAULT NULL,
  `direction` varchar(255) DEFAULT NULL,
  `tag_number` varchar(255) DEFAULT NULL,
  `plate` varchar(255) DEFAULT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `trans_date` varchar(255) DEFAULT NULL,
  `trans_code` varchar(255) DEFAULT NULL,
  `rider_id` varchar(255) DEFAULT NULL,
  `bike_id` varchar(255) DEFAULT NULL,
  `admin_charges` varchar(255) DEFAULT NULL,
  `salik_account_id` varchar(255) DEFAULT NULL,
  `attachments` varchar(255) DEFAULT NULL,
  `total_amount` varchar(255) DEFAULT NULL,
  `pay_account` varchar(255) DEFAULT NULL,
  `details` longtext DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saliks_deleted_at_index` (`deleted_at`),
  KEY `saliks_branch_id_index` (`branch_id`),
  KEY `idx_saliks_company_id` (`company_id`),
  CONSTRAINT `fk_saliks_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12913 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saliks');
    }
};