<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trans_codes')) {
            Schema::create('trans_codes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('last_trans_code')->default(0);
                $table->timestamps();

                $table->unique('company_id');
            });
        }

        $this->seedCompanyRows();
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_codes');
    }

    private function seedCompanyRows(): void
    {
        if (! Schema::hasTable('trans_codes')) {
            return;
        }

        $companyIds = collect();

        if (Schema::hasTable('companies')) {
            $companyIds = $companyIds->merge(DB::table('companies')->pluck('id'));
        }

        if (Schema::hasTable('transactions')) {
            $companyIds = $companyIds->merge(
                DB::table('transactions')->whereNotNull('company_id')->distinct()->pluck('company_id')
            );
        }

        if (Schema::hasTable('vouchers')) {
            $companyIds = $companyIds->merge(
                DB::table('vouchers')->whereNotNull('company_id')->distinct()->pluck('company_id')
            );
        }

        $existing = DB::table('trans_codes')->pluck('company_id')->all();
        $now = now();

        foreach ($companyIds->unique()->filter()->values() as $companyId) {
            if (in_array($companyId, $existing, false)) {
                continue;
            }

            $max = 0;

            if (Schema::hasTable('transactions')) {
                $max = max($max, (int) DB::table('transactions')->where('company_id', $companyId)->max('trans_code'));
            }

            if (Schema::hasTable('vouchers')) {
                $max = max($max, (int) DB::table('vouchers')->where('company_id', $companyId)->max('trans_code'));
            }

            DB::table('trans_codes')->insert([
                'company_id' => $companyId,
                'last_trans_code' => $max,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
