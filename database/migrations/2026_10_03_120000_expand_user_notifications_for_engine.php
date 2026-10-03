<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::table('user_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('user_notifications', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('company_id')->index();
            }
            if (! Schema::hasColumn('user_notifications', 'severity')) {
                $table->string('severity', 20)->default('info')->after('type');
            }
            if (! Schema::hasColumn('user_notifications', 'source_type')) {
                $table->string('source_type', 100)->nullable()->after('data');
            }
            if (! Schema::hasColumn('user_notifications', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('user_notifications', 'action_url')) {
                $table->string('action_url', 500)->nullable()->after('source_id');
            }
            if (! Schema::hasColumn('user_notifications', 'rule_id')) {
                $table->unsignedBigInteger('rule_id')->nullable()->after('action_url')->index();
            }
            if (! Schema::hasColumn('user_notifications', 'dedupe_key')) {
                $table->string('dedupe_key', 191)->nullable()->after('rule_id');
            }
            if (! Schema::hasColumn('user_notifications', 'dismissed_at')) {
                $table->timestamp('dismissed_at')->nullable()->after('read_at');
            }
        });

        if (Schema::hasColumn('user_notifications', 'dedupe_key')) {
            $rows = DB::table('user_notifications')->whereNull('dedupe_key')->orderBy('id')->get(['id', 'company_id', 'type']);
            foreach ($rows as $row) {
                DB::table('user_notifications')->where('id', $row->id)->update([
                    'dedupe_key' => sprintf(
                        '%s|%s|legacy|%s|row:%s',
                        (string) ($row->company_id ?? 0),
                        (string) ($row->type ?? 'unknown'),
                        (string) $row->id,
                        (string) $row->id
                    ),
                ]);
            }

            try {
                Schema::table('user_notifications', function (Blueprint $table) {
                    $table->unique('dedupe_key');
                });
            } catch (\Throwable $e) {
                // unique already exists
            }
        }

        try {
            Schema::table('user_notifications', function (Blueprint $table) {
                $table->index(['user_id', 'read_at', 'created_at'], 'user_notifications_user_read_created_idx');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('user_notifications', function (Blueprint $table) {
                $table->index(['company_id', 'user_id', 'created_at'], 'user_notifications_company_user_created_idx');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('user_notifications', function (Blueprint $table) {
                $table->index(['source_type', 'source_id'], 'user_notifications_source_idx');
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::table('user_notifications', function (Blueprint $table) {
            foreach ([
                'user_notifications_user_read_created_idx',
                'user_notifications_company_user_created_idx',
                'user_notifications_source_idx',
            ] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable $e) {
                }
            }

            try {
                $table->dropUnique(['dedupe_key']);
            } catch (\Throwable $e) {
            }

            $cols = [
                'branch_id', 'severity', 'source_type', 'source_id',
                'action_url', 'rule_id', 'dedupe_key', 'dismissed_at',
            ];
            $drop = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('user_notifications', $c)));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
