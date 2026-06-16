<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('license_expenses')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `license_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `trans_date` varchar(255) DEFAULT NULL,
  `trans_code` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `rider_id` varchar(255) DEFAULT NULL,
  `expense_account_id` bigint(20) unsigned DEFAULT NULL,
  `license_status` varchar(255) DEFAULT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) DEFAULT NULL,
  `billing_month` varchar(255) DEFAULT NULL,
  `pay_account` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `license_expenses_rider_id_index` (`rider_id`),
  KEY `license_expenses_trans_code_index` (`trans_code`),
  KEY `license_expenses_license_status_index` (`license_status`),
  KEY `license_expenses_payment_status_index` (`payment_status`),
  KEY `license_expenses_billing_month_index` (`billing_month`),
  KEY `license_expenses_trans_date_index` (`trans_date`),
  KEY `license_expenses_deleted_at_index` (`deleted_at`),
  KEY `license_expenses_expense_account_id_index` (`expense_account_id`),
  KEY `idx_license_expenses_company_id` (`company_id`),
  CONSTRAINT `fk_license_expenses_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('license_expenses');
    }
};
