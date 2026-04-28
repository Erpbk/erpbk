<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('riders') || !Schema::hasColumn('riders', 'personal_email')) {
            return;
        }

        // Preserve data by backfilling email where empty.
        DB::table('riders')
            ->whereNotNull('personal_email')
            ->where(function ($query) {
                $query->whereNull('email')->orWhere('email', '');
            })
            ->update(['email' => DB::raw('personal_email')]);

        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn('personal_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('riders') || Schema::hasColumn('riders', 'personal_email')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            $table->string('personal_email')->nullable()->after('company_contact');
        });
    }
};
