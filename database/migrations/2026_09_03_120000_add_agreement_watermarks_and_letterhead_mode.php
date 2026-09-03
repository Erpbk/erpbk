<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agreement_letterheads') && ! Schema::hasColumn('agreement_letterheads', 'kind')) {
            Schema::table('agreement_letterheads', function (Blueprint $table) {
                $table->string('kind', 20)->default('letterhead')->after('name');
                $table->index(['company_id', 'kind']);
            });
        }

        if (Schema::hasTable('agreement_categories')) {
            Schema::table('agreement_categories', function (Blueprint $table) {
                if (! Schema::hasColumn('agreement_categories', 'letterhead_mode')) {
                    $table->string('letterhead_mode', 16)->default('default')->after('letterhead_id');
                }
                if (! Schema::hasColumn('agreement_categories', 'watermark_id')) {
                    $table->unsignedBigInteger('watermark_id')->nullable()->after('letterhead_mode');
                }
            });

            if (Schema::hasColumn('agreement_categories', 'letterhead_id')
                && Schema::hasColumn('agreement_categories', 'letterhead_mode')) {
                DB::table('agreement_categories')
                    ->whereNotNull('letterhead_id')
                    ->update(['letterhead_mode' => 'library']);
            }

            if (Schema::hasColumn('agreement_categories', 'watermark_id')) {
                Schema::table('agreement_categories', function (Blueprint $table) {
                    $table->foreign('watermark_id')
                        ->references('id')
                        ->on('agreement_letterheads')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agreement_categories') && Schema::hasColumn('agreement_categories', 'watermark_id')) {
            Schema::table('agreement_categories', function (Blueprint $table) {
                $table->dropForeign(['watermark_id']);
                $table->dropColumn('watermark_id');
            });
        }

        if (Schema::hasTable('agreement_categories') && Schema::hasColumn('agreement_categories', 'letterhead_mode')) {
            Schema::table('agreement_categories', function (Blueprint $table) {
                $table->dropColumn('letterhead_mode');
            });
        }

        if (Schema::hasTable('agreement_letterheads') && Schema::hasColumn('agreement_letterheads', 'kind')) {
            Schema::table('agreement_letterheads', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'kind']);
                $table->dropColumn('kind');
            });
        }
    }
};
