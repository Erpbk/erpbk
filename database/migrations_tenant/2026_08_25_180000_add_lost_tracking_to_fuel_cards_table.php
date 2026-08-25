<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks a fuel card that was lost or never returned. The rider is charged via an
 * Inventory Loss (IL) voucher, so the resulting voucher and trans_code are stored
 * on the card to keep the ledger entry reachable from the card page.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $columns = [
        'lost_date' => 'date',
        'lost_rider_id' => 'bigint',
        'lost_amount' => 'decimal',
        'lost_voucher_id' => 'bigint',
        'lost_trans_code' => 'string',
        'lost_remarks' => 'text',
        'lost_by' => 'bigint',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            foreach ($this->columns as $column => $type) {
                if (Schema::hasColumn('fuel_cards', $column)) {
                    continue;
                }

                match ($type) {
                    'date' => $table->date($column)->nullable(),
                    'bigint' => $table->unsignedBigInteger($column)->nullable(),
                    'decimal' => $table->decimal($column, 12, 2)->nullable(),
                    'string' => $table->string($column, 100)->nullable(),
                    'text' => $table->text($column)->nullable(),
                };
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        $existing = array_values(array_filter(
            array_keys($this->columns),
            fn (string $column) => Schema::hasColumn('fuel_cards', $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
