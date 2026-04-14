<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sim_histories')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `sim_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sim_id` bigint(20) unsigned NOT NULL,
  `rider_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `note_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `assigned_by` varchar(191) DEFAULT NULL,
  `returned_by` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sim_histories_sim_id_foreign` (`sim_id`),
  KEY `sim_histories_rider_id_foreign` (`rider_id`),
  KEY `idx_sim_histories_company_id` (`company_id`),
  CONSTRAINT `fk_sim_histories_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sim_histories_rider_id_foreign` FOREIGN KEY (`rider_id`) REFERENCES `riders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sim_histories_sim_id_foreign` FOREIGN KEY (`sim_id`) REFERENCES `sims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=340 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_histories');
    }
};