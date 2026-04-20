<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receipts')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `account_id` bigint(20) DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `leasing_company_id` int(10) unsigned DEFAULT NULL,
  `payer_account_id` varchar(255) DEFAULT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `amount_type` varchar(255) DEFAULT NULL,
  `date_of_receipt` date DEFAULT NULL,
  `billing_month` date DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `voucher_id` bigint(20) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipts_branch_id_index` (`branch_id`),
  KEY `idx_receipts_company_id` (`company_id`),
  CONSTRAINT `fk_receipts_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};