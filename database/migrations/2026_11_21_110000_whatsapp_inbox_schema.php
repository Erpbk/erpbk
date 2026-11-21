<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_messages')) {
            if (Schema::hasColumn('whatsapp_messages', 'sent_by_user_id')
                && ! Schema::hasColumn('whatsapp_messages', 'user_id')) {
                DB::statement('ALTER TABLE `whatsapp_messages` CHANGE `sent_by_user_id` `user_id` BIGINT UNSIGNED NULL');
            }

            Schema::table('whatsapp_messages', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_messages', 'participant_conflict')) {
                    $table->json('participant_conflict')->nullable();
                }
                if (! Schema::hasColumn('whatsapp_messages', 'is_system')) {
                    $table->boolean('is_system')->default(false);
                }
            });

            $this->addIndexIfMissing(
                'whatsapp_messages',
                'wa_messages_company_participant_occurred_idx',
                ['company_id', 'participant_type', 'participant_id', 'occurred_at']
            );
            $this->addIndexIfMissing(
                'whatsapp_messages',
                'wa_messages_company_peer_conn_occurred_idx',
                ['company_id', 'peer_phone', 'whatsapp_connection_id', 'occurred_at']
            );
            $this->addIndexIfMissing(
                'whatsapp_messages',
                'wa_messages_company_user_occurred_idx',
                ['company_id', 'user_id', 'occurred_at']
            );
        }

        if (! Schema::hasTable('whatsapp_unknown_inbox_settings')) {
            Schema::create('whatsapp_unknown_inbox_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('whatsapp_connection_id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->timestamps();

                $table->unique(
                    ['company_id', 'whatsapp_connection_id'],
                    'wa_unknown_inbox_company_connection_uidx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_unknown_inbox_settings');

        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        foreach ([
            'wa_messages_company_participant_occurred_idx',
            'wa_messages_company_peer_conn_occurred_idx',
            'wa_messages_company_user_occurred_idx',
        ] as $index) {
            try {
                Schema::table('whatsapp_messages', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'participant_conflict')) {
                $table->dropColumn('participant_conflict');
            }
            if (Schema::hasColumn('whatsapp_messages', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });

        if (Schema::hasColumn('whatsapp_messages', 'user_id')
            && ! Schema::hasColumn('whatsapp_messages', 'sent_by_user_id')) {
            DB::statement('ALTER TABLE `whatsapp_messages` CHANGE `user_id` `sent_by_user_id` BIGINT UNSIGNED NULL');
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]))
            ->isNotEmpty();
        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName, $columns) {
            $table->index($columns, $indexName);
        });
    }
};
