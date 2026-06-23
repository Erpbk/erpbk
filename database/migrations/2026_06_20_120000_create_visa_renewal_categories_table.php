<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visa_renewal_categories')) {
            Schema::create('visa_renewal_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedInteger('display_order')->default(1)->index();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        $defaultId = DB::table('visa_renewal_categories')->where('is_default', true)->value('id');
        if (!$defaultId) {
            $defaultId = DB::table('visa_renewal_categories')->insertGetId([
                'name' => 'New Visa',
                'display_order' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!Schema::hasColumn('visa_expenses', 'renewal_category_id')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('renewal_category_id')->nullable()->after('expense_account_id');
                $table->index('renewal_category_id', 'visa_expenses_renewal_category_id_index');
            });
        }

        DB::table('visa_expenses')
            ->whereNull('renewal_category_id')
            ->update(['renewal_category_id' => $defaultId]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('visa_expenses', 'renewal_category_id')) {
            Schema::table('visa_expenses', function (Blueprint $table) {
                $table->dropIndex('visa_expenses_renewal_category_id_index');
                $table->dropColumn('renewal_category_id');
            });
        }

        Schema::dropIfExists('visa_renewal_categories');
    }
};
