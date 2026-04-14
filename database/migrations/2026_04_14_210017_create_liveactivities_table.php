<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('liveactivities')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `liveactivities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `rider_id` bigint(20) unsigned DEFAULT NULL,
  `d_rider_id` bigint(20) unsigned DEFAULT NULL,
  `payout_type` varchar(50) DEFAULT NULL,
  `delivered_orders` int(11) DEFAULT NULL,
  `ontime_orders` int(11) DEFAULT NULL,
  `ontime_orders_percentage` decimal(6,2) DEFAULT NULL,
  `avg_time` decimal(6,2) DEFAULT NULL,
  `rejected_orders` int(11) DEFAULT NULL,
  `rejected_orders_percentage` decimal(6,2) DEFAULT NULL,
  `login_hr` decimal(6,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `delivery_rating` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_liveactivities_company_id` (`company_id`),
  CONSTRAINT `fk_liveactivities_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8512 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('liveactivities');
    }
};