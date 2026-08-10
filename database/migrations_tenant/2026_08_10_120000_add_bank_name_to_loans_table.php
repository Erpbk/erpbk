<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        if (! Schema::hasColumn('loans', 'bank_name')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->string('bank_name')->nullable()->after('loan_number');
            });
        }

        // Backfill from legacy bank_id where possible, then clear bank_id.
        if (Schema::hasColumn('loans', 'bank_id') && Schema::hasTable('banks')) {
            DB::statement('
                UPDATE loans
                INNER JOIN banks ON banks.id = loans.bank_id
                SET loans.bank_name = banks.name
                WHERE (loans.bank_name IS NULL OR loans.bank_name = "")
                  AND loans.bank_id IS NOT NULL
            ');

            DB::table('loans')->whereNotNull('bank_id')->update(['bank_id' => null]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('loans') || ! Schema::hasColumn('loans', 'bank_name')) {
            return;
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('bank_name');
        });
    }
};
