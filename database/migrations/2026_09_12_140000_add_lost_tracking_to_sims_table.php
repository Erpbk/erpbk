<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks a SIM that was lost or never returned. The holding rider/employee is
 * charged via an Inventory Loss (IL) voucher; voucher + trans_code are stored
 * on the SIM so the ledger entry stays reachable from the SIM page.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $columns = [
        'lost_date' => 'date',
        'lost_rider_id' => 'bigint',
        'lost_employee_id' => 'bigint',
        'lost_amount' => 'decimal',
        'lost_voucher_id' => 'bigint',
        'lost_trans_code' => 'string',
        'lost_remarks' => 'text',
        'lost_by' => 'bigint',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('sims')) {
            return;
        }

        Schema::table('sims', function (Blueprint $table) {
            foreach ($this->columns as $column => $type) {
                if (Schema::hasColumn('sims', $column)) {
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
        if (! Schema::hasTable('sims')) {
            return;
        }

        $existing = array_values(array_filter(
            array_keys($this->columns),
            fn (string $column) => Schema::hasColumn('sims', $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('sims', function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
