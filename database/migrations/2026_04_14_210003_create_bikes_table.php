<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bikes')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `bikes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `plate` varchar(100) NOT NULL,
  `vehicle_type` varchar(100) NOT NULL,
  `chassis_number` varchar(100) NOT NULL,
  `color` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `model_type` varchar(100) NOT NULL,
  `engine` varchar(100) NOT NULL,
  `company` int(11) DEFAULT NULL,
  `rider_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse` varchar(50) DEFAULT 'Active',
  `traffic_file_number` varchar(100) DEFAULT NULL,
  `emirates` varchar(100) DEFAULT NULL,
  `bike_code` varchar(100) DEFAULT '',
  `registration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `insurance_co` varchar(255) DEFAULT NULL,
  `policy_no` varchar(100) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `contract_number` varchar(50) DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `current_km` decimal(10,3) DEFAULT NULL,
  `previous_km` decimal(10,3) DEFAULT NULL,
  `maintenance_km` decimal(10,3) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bikes_rider_id_warehouse` (`rider_id`,`warehouse`),
  KEY `bikes_company_index` (`company`),
  KEY `idx_bikes_company_id` (`company_id`),
  CONSTRAINT `fk_bikes_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=807 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bikes');
    }
};