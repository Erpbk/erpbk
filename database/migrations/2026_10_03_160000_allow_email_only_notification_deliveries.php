<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_deliveries')) {
            return;
        }

        Schema::table('notification_deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_deliveries', 'to_email')) {
                $table->string('to_email', 255)->nullable()->after('channel');
            }
        });

        // Drop FK so we can allow null user_notification_id for email-only sends.
        $this->dropUserNotificationForeign();

        DB::statement('ALTER TABLE notification_deliveries MODIFY user_notification_id BIGINT UNSIGNED NULL');

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->foreign('user_notification_id')
                ->references('id')
                ->on('user_notifications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_deliveries')) {
            return;
        }

        $this->dropUserNotificationForeign();

        // Clear orphaned email-only rows before restoring NOT NULL.
        DB::table('notification_deliveries')->whereNull('user_notification_id')->delete();

        if (Schema::hasColumn('notification_deliveries', 'to_email')) {
            Schema::table('notification_deliveries', function (Blueprint $table) {
                $table->dropColumn('to_email');
            });
        }

        DB::statement('ALTER TABLE notification_deliveries MODIFY user_notification_id BIGINT UNSIGNED NOT NULL');

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->foreign('user_notification_id')
                ->references('id')
                ->on('user_notifications')
                ->cascadeOnDelete();
        });
    }

    private function dropUserNotificationForeign(): void
    {
        try {
            Schema::table('notification_deliveries', function (Blueprint $table) {
                $table->dropForeign(['user_notification_id']);
            });
        } catch (\Throwable $e) {
            try {
                DB::statement('ALTER TABLE notification_deliveries DROP FOREIGN KEY notification_deliveries_user_notification_id_foreign');
            } catch (\Throwable $e2) {
                // ignore — may already be absent
            }
        }
    }
};
