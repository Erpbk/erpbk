<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_activities')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `rider_activities` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `rider_id` bigint(20) DEFAULT NULL,
  `d_rider_id` bigint(20) DEFAULT NULL,
  `payout_type` varchar(50) DEFAULT '',
  `delivered_orders` int(11) DEFAULT NULL,
  `ontime_orders` int(11) DEFAULT NULL,
  `ontime_orders_percentage` decimal(6,2) DEFAULT NULL,
  `avg_time` decimal(6,2) DEFAULT NULL,
  `rejected_orders` int(11) DEFAULT NULL,
  `rejected_orders_percentage` decimal(6,2) DEFAULT NULL,
  `login_hr` decimal(6,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `delivery_rating` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rider_activities_company_id` (`company_id`),
  CONSTRAINT `fk_rider_activities_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87874 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_activities');
    }
};