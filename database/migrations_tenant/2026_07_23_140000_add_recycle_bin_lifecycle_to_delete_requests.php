<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delete_requests')) {
            return;
        }

        Schema::table('delete_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('delete_requests', 'moved_to_bin_at')) {
                $table->timestamp('moved_to_bin_at')->nullable()->after('admin_remarks');
            }
            if (! Schema::hasColumn('delete_requests', 'restored_by')) {
                $table->unsignedBigInteger('restored_by')->nullable()->index()->after('moved_to_bin_at');
            }
            if (! Schema::hasColumn('delete_requests', 'restored_at')) {
                $table->timestamp('restored_at')->nullable()->after('restored_by');
            }
            if (! Schema::hasColumn('delete_requests', 'permanently_deleted_by')) {
                $table->unsignedBigInteger('permanently_deleted_by')->nullable()->index()->after('restored_at');
            }
            if (! Schema::hasColumn('delete_requests', 'permanently_deleted_at')) {
                $table->timestamp('permanently_deleted_at')->nullable()->after('permanently_deleted_by');
            }
            if (! Schema::hasColumn('delete_requests', 'bin_outcome')) {
                $table->string('bin_outcome', 40)->nullable()->index()->after('permanently_deleted_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('delete_requests')) {
            return;
        }

        Schema::table('delete_requests', function (Blueprint $table) {
            foreach ([
                'moved_to_bin_at',
                'restored_by',
                'restored_at',
                'permanently_deleted_by',
                'permanently_deleted_at',
                'bin_outcome',
            ] as $column) {
                if (Schema::hasColumn('delete_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
