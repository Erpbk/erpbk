<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `trans_date` date DEFAULT NULL,
  `trans_code` bigint(20) NOT NULL,
  `reference_id` bigint(20) NOT NULL,
  `reference_type` varchar(100) NOT NULL,
  `account_id` bigint(20) NOT NULL,
  `billing_month` date NOT NULL,
  `narration` longtext DEFAULT NULL,
  `debit` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '0.00',
  `credit` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_transactions_account_id` (`account_id`),
  KEY `transactions_deleted_at_index` (`deleted_at`),
  KEY `transactions_reference_id_index` (`reference_id`),
  KEY `transactions_reference_type_index` (`reference_type`),
  KEY `transactions_account_id_index` (`account_id`),
  KEY `transactions_trans_code_index` (`trans_code`),
  KEY `transactions_reference_type_id_index` (`reference_type`,`reference_id`),
  KEY `idx_transactions_company_id` (`company_id`),
  CONSTRAINT `fk_transactions_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};