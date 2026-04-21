<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `accounts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `account_code` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `account_type` varchar(50) NOT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ref_name` varchar(100) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `ref_id` bigint(20) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `notes` varchar(500) DEFAULT NULL,
  `account_tax` varchar(255) DEFAULT NULL,
  `traffic_code_number` varchar(255) DEFAULT NULL,
  `admin_charges` varchar(255) DEFAULT NULL,
  `is_locked` varchar(255) DEFAULT '1',
  `custom_field_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_field_values`)),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_accounts_id` (`id`),
  KEY `accounts_deleted_at_index` (`deleted_at`),
  KEY `accounts_branch_id_index` (`branch_id`),
  KEY `idx_accounts_company_id` (`company_id`),
  CONSTRAINT `fk_accounts_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2459 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};