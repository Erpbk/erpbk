<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        // Thread tip / list grouping for a user's inbox.
        $this->addIndexIfMissing(
            'whatsapp_messages',
            'wa_messages_company_user_peer_part_idx',
            ['company_id', 'user_id', 'peer_phone', 'participant_type', 'participant_id']
        );

        // Unread badge aggregation.
        $this->addIndexIfMissing(
            'whatsapp_messages',
            'wa_messages_company_user_dir_status_idx',
            ['company_id', 'user_id', 'direction', 'status']
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        foreach ([
            'wa_messages_company_user_peer_part_idx',
            'wa_messages_company_user_dir_status_idx',
        ] as $index) {
            try {
                Schema::table('whatsapp_messages', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            } catch (\Throwable $e) {
                // ignore
            }
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
