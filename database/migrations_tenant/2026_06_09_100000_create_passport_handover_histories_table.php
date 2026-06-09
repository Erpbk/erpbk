<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('passport_handover_histories')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `passport_handover_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `rider_id` bigint(20) unsigned DEFAULT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `holder_type` varchar(20) NOT NULL DEFAULT 'rider' COMMENT 'rider or employee',
  `holder_name` varchar(255) DEFAULT NULL COMMENT 'Passport holder name',
  `passport_number` varchar(100) DEFAULT NULL,
  `handed_over_by` varchar(255) DEFAULT NULL COMMENT 'Person who issued the passport',
  `received_by` varchar(255) DEFAULT NULL COMMENT 'Person who collected the passport on issue',
  `returned_by` varchar(255) DEFAULT NULL COMMENT 'Person who returned the passport',
  `return_received_by` varchar(255) DEFAULT NULL COMMENT 'Person who received the passport on return',
  `note_date` datetime DEFAULT NULL COMMENT 'Issue date and time',
  `return_date` datetime DEFAULT NULL COMMENT 'Return date and time',
  `remarks` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'issued' COMMENT 'issued or returned',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_passport_handover_histories_company_id` (`company_id`),
  KEY `idx_passport_handover_histories_rider_id` (`rider_id`),
  KEY `idx_passport_handover_histories_employee_id` (`employee_id`),
  KEY `idx_passport_handover_histories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_handover_histories');
    }
};
