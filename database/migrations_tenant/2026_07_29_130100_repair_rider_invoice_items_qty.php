<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historic rows stored qty rounded up while amount kept the exact quantity,
     * so Qty x Rate did not equal Amount on the invoice. Rebuild qty from the
     * stored money values. Only qty is touched — no amount/total is changed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('rider_invoice_items')) {
            return;
        }

        foreach (['qty', 'rate', 'amount'] as $column) {
            if (! Schema::hasColumn('rider_invoice_items', $column)) {
                return;
            }
        }

        $hasDiscount = Schema::hasColumn('rider_invoice_items', 'discount');
        $discount = $hasDiscount ? 'COALESCE(discount, 0)' : '0';

        DB::statement(<<<SQL
UPDATE `rider_invoice_items`
SET `qty` = ROUND((COALESCE(`amount`, 0) + {$discount}) / `rate`, 2)
WHERE `rate` IS NOT NULL
  AND `rate` <> 0
  AND `amount` IS NOT NULL
  AND ABS((COALESCE(`qty`, 0) * `rate`) - {$discount} - `amount`) > 0.01
SQL);
    }

    public function down(): void
    {
        // Irreversible data repair.
    }
};
