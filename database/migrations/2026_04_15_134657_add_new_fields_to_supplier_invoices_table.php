<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensures supplier_invoices exists with expected columns.
     *
     * Some environments may not run the dedicated create migration; if the base table is
     * missing, we CREATE it here instead of ALTER (avoids 1146 on deploy).
     */
    public function up(): void
    {
        if (!Schema::hasTable('supplier_invoices')) {
            $this->createSupplierInvoicesTableFromScratch();

            return;
        }

        $candidateColumns = [
            'subtotal',
            'vat',
            'partial_paid_amount',
            'status',
            'is_order',
            'is_invoice',
            'attachment',
            'created_by',
            'updated_by',
            'order_date',
        ];
        $missing = array_values(array_filter($candidateColumns, fn ($c) => !Schema::hasColumn('supplier_invoices', $c)));
        if ($missing === []) {
            return;
        }

        Schema::table('supplier_invoices', function (Blueprint $table) use ($missing) {
            if (in_array('subtotal', $missing, true)) {
                $table->decimal('subtotal', 10, 2)->unsigned()->default(0);
            }
            if (in_array('vat', $missing, true)) {
                $table->decimal('vat', 8, 2)->unsigned()->default(0);
            }
            if (in_array('partial_paid_amount', $missing, true)) {
                $table->string('partial_paid_amount')->nullable();
            }
            if (in_array('status', $missing, true)) {
                $table->string('status')->default('unpaid');
            }
            if (in_array('is_order', $missing, true)) {
                $table->boolean('is_order')->default(false);
            }
            if (in_array('is_invoice', $missing, true)) {
                $table->boolean('is_invoice')->default(false);
            }
            if (in_array('attachment', $missing, true)) {
                $table->string('attachment')->nullable();
            }
            if (in_array('created_by', $missing, true)) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (in_array('updated_by', $missing, true)) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
            if (in_array('order_date', $missing, true)) {
                $table->date('order_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('supplier_invoices')) {
            return;
        }

        $columns = [
            'subtotal',
            'vat',
            'partial_paid_amount',
            'status',
            'is_order',
            'is_invoice',
            'attachment',
            'created_by',
            'updated_by',
            'order_date',
        ];
        $toDrop = array_values(array_filter($columns, fn ($c) => Schema::hasColumn('supplier_invoices', $c)));
        if ($toDrop === []) {
            return;
        }

        Schema::table('supplier_invoices', function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }

    private function createSupplierInvoicesTableFromScratch(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement(<<<'SQL'
CREATE TABLE `supplier_invoices` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `inv_id` varchar(50) DEFAULT NULL,
  `inv_date` date DEFAULT NULL,
  `supplier_id` bigint(20) DEFAULT NULL,
  `month_invoice` int(11) DEFAULT NULL,
  `descriptions` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `billing_month` date DEFAULT NULL,
  `gaurantee` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `subtotal` decimal(10,2) unsigned NOT NULL DEFAULT 0,
  `vat` decimal(8,2) unsigned NOT NULL DEFAULT 0,
  `partial_paid_amount` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `is_order` tinyint(1) NOT NULL DEFAULT 0,
  `is_invoice` tinyint(1) NOT NULL DEFAULT 0,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_supplier_invoices_company_id` (`company_id`),
  CONSTRAINT `fk_supplier_invoices_company_id_companies_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
