<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cheques')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `cheques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `cheque_number` varchar(255) NOT NULL,
  `bank_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payee_account` varchar(255) DEFAULT NULL,
  `payee_name` varchar(255) DEFAULT NULL,
  `payer_account` varchar(255) DEFAULT NULL,
  `payer_name` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `cheque_date` date DEFAULT NULL,
  `cleared_date` date DEFAULT NULL,
  `returned_date` date DEFAULT NULL,
  `stop_payment_date` date DEFAULT NULL,
  `billing_month` date DEFAULT NULL,
  `status` enum('Issued','Cleared','Returned','Stop Payment','Lost') NOT NULL DEFAULT 'Issued',
  `return_reason` varchar(255) DEFAULT NULL,
  `stop_payment_reason` varchar(255) DEFAULT NULL,
  `type` enum('payable','receiveable') NOT NULL,
  `is_security` tinyint(1) NOT NULL DEFAULT 0,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `issued_by` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheques_branch_id_index` (`branch_id`),
  KEY `idx_cheques_company_id` (`company_id`),
  CONSTRAINT `fk_cheques_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};