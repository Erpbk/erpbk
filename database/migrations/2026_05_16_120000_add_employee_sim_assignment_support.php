<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sims') && !Schema::hasColumn('sims', 'assign_type')) {
            Schema::table('sims', function (Blueprint $table) {
                $table->string('assign_type', 20)->nullable()->after('assign_to');
            });

            DB::table('sims')
                ->whereNotNull('assign_to')
                ->whereNull('assign_type')
                ->update(['assign_type' => 'rider']);
        }

        if (Schema::hasTable('sim_histories') && !Schema::hasColumn('sim_histories', 'employee_id')) {
            Schema::table('sim_histories', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('rider_id');
                $table->index('employee_id');
            });
        }

        if (!Schema::hasTable('employee_histories')) {
            Schema::create('employee_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('event_type', 64);
                $table->string('title', 255);
                $table->text('details')->nullable();
                $table->json('meta')->nullable();
                $table->date('effective_date');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'effective_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_histories');

        if (Schema::hasTable('sim_histories') && Schema::hasColumn('sim_histories', 'employee_id')) {
            Schema::table('sim_histories', function (Blueprint $table) {
                $table->dropIndex(['employee_id']);
                $table->dropColumn('employee_id');
            });
        }

        if (Schema::hasTable('sims') && Schema::hasColumn('sims', 'assign_type')) {
            Schema::table('sims', function (Blueprint $table) {
                $table->dropColumn('assign_type');
            });
        }
    }
};
