<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visa_expenses')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `visa_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `trans_date` varchar(255) DEFAULT NULL,
  `trans_code` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `rider_id` varchar(255) DEFAULT NULL,
  `visa_status` varchar(255) DEFAULT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) DEFAULT NULL,
  `billing_month` varchar(255) DEFAULT NULL,
  `pay_account` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_expenses_rider_id_index` (`rider_id`),
  KEY `visa_expenses_trans_code_index` (`trans_code`),
  KEY `visa_expenses_visa_status_index` (`visa_status`),
  KEY `visa_expenses_payment_status_index` (`payment_status`),
  KEY `visa_expenses_billing_month_index` (`billing_month`),
  KEY `visa_expenses_trans_date_index` (`trans_date`),
  KEY `visa_expenses_deleted_at_index` (`deleted_at`),
  KEY `idx_visa_expenses_company_id` (`company_id`),
  CONSTRAINT `fk_visa_expenses_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2017 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_expenses');
    }
};