<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_accounts')) {
            return;
        }

        if (!Schema::hasColumn('expense_accounts', 'renewal_category_id')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('renewal_category_id')->nullable()->after('rider_id');
                $table->index('renewal_category_id', 'expense_accounts_renewal_category_id_index');
            });
        }

        $defaultId = DB::table('visa_renewal_categories')->where('is_default', true)->value('id');
        if (!$defaultId && Schema::hasTable('visa_renewal_categories')) {
            $defaultId = DB::table('visa_renewal_categories')->orderBy('display_order')->orderBy('id')->value('id');
        }

        if ($defaultId) {
            DB::table('expense_accounts')
                ->whereNull('renewal_category_id')
                ->whereNotNull('rider_id')
                ->update(['renewal_category_id' => $defaultId]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('expense_accounts') || !Schema::hasColumn('expense_accounts', 'renewal_category_id')) {
            return;
        }

        Schema::table('expense_accounts', function (Blueprint $table) {
            $table->dropIndex('expense_accounts_renewal_category_id_index');
            $table->dropColumn('renewal_category_id');
        });
    }
};
