<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agreement_categories') || Schema::hasColumn('agreement_categories', 'watermark_mode')) {
            return;
        }

        Schema::table('agreement_categories', function (Blueprint $table) {
            $table->string('watermark_mode', 16)->default('none')->after('watermark_id');
        });

        if (Schema::hasColumn('agreement_categories', 'watermark_id')) {
            DB::table('agreement_categories')
                ->whereNotNull('watermark_id')
                ->update(['watermark_mode' => 'library']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agreement_categories') && Schema::hasColumn('agreement_categories', 'watermark_mode')) {
            Schema::table('agreement_categories', function (Blueprint $table) {
                $table->dropColumn('watermark_mode');
            });
        }
    }
};
