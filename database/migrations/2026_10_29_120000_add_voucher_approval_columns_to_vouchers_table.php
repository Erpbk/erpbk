<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'approval_status')) {
                $table->string('approval_status', 32)->default('pending_approval')->after('status')->index();
            }
            if (! Schema::hasColumn('vouchers', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('vouchers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('vouchers', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('vouchers', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('vouchers', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('vouchers', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('vouchers', 'approval_locked_at')) {
                $table->timestamp('approval_locked_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('vouchers', 'approval_posting_payload')) {
                $table->json('approval_posting_payload')->nullable()->after('approval_locked_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $columns = [
                'approval_status',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'submitted_at',
                'approval_locked_at',
                'approval_posting_payload',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('vouchers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
