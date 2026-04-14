<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_invoices')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `rider_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `inv_date` date NOT NULL,
  `rider_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `zone` varchar(191) DEFAULT NULL,
  `login_hours` bigint(20) DEFAULT NULL,
  `working_days` bigint(20) DEFAULT NULL,
  `perfect_attendance` double(8,2) DEFAULT NULL,
  `rejection` bigint(20) DEFAULT NULL,
  `performance` varchar(20) DEFAULT NULL,
  `off` varchar(20) DEFAULT NULL,
  `month_invoice` int(11) DEFAULT NULL,
  `descriptions` text DEFAULT NULL,
  `total_amount` double(20,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `billing_month` date DEFAULT NULL,
  `gaurantee` varchar(255) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `vat` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `status` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rider_invoices_deleted_at_index` (`deleted_at`),
  KEY `idx_rider_invoices_company_id` (`company_id`),
  CONSTRAINT `fk_rider_invoices_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16652 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_invoices');
    }
};