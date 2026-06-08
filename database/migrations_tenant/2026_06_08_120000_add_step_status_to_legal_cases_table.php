<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('legal_cases')) {
            return;
        }

        if (!Schema::hasColumn('legal_cases', 'step_status')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                $table->enum('step_status', ['pending', 'completed'])
                    ->default('pending')
                    ->after(Schema::hasColumn('legal_cases', 'expiry_date') ? 'expiry_date' : 'reference_number')
                    ->index();
            });
        }

        if (Schema::hasColumn('legal_cases', 'payment_status')) {
            DB::table('legal_cases')
                ->where('payment_status', 'paid')
                ->update(['step_status' => 'completed']);

            DB::table('legal_cases')
                ->where(function ($q) {
                    $q->where('payment_status', 'unpaid')
                        ->orWhereNull('payment_status')
                        ->orWhere('payment_status', '');
                })
                ->update(['step_status' => 'pending']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('legal_cases') && Schema::hasColumn('legal_cases', 'step_status')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                $table->dropIndex(['step_status']);
                $table->dropColumn('step_status');
            });
        }
    }
};
