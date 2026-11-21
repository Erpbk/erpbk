<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_connections')) {
            return;
        }

        if (! Schema::hasColumn('whatsapp_connections', 'is_default')) {
            Schema::table('whatsapp_connections', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('company_id');
            });
        }

        // Drop company-scoped unique on phone_number_id if present.
        $this->tryDropUnique('whatsapp_connections', 'whatsapp_connections_company_id_phone_number_id_unique');

        // phone_number_id must be globally unique across platform numbers
        $this->tryAddUnique('whatsapp_connections', 'phone_number_id', 'whatsapp_connections_phone_number_id_unique');

        // Ensure at most one default flag is set among existing rows.
        $defaultId = DB::table('whatsapp_connections')
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        if ($defaultId) {
            DB::table('whatsapp_connections')
                ->where('is_default', true)
                ->where('id', '!=', $defaultId)
                ->update(['is_default' => false]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_connections')) {
            return;
        }

        $this->tryDropUnique('whatsapp_connections', 'whatsapp_connections_phone_number_id_unique');

        if (Schema::hasColumn('whatsapp_connections', 'is_default')) {
            Schema::table('whatsapp_connections', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }

        $this->tryAddUnique(
            'whatsapp_connections',
            ['company_id', 'phone_number_id'],
            'whatsapp_connections_company_id_phone_number_id_unique'
        );
    }

    /**
     * @param  string|list<string>  $columns
     */
    private function tryAddUnique(string $table, string|array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->unique($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Index already exists or driver difference — ignore.
        }
    }

    private function tryDropUnique(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropUnique($indexName);
            });
        } catch (\Throwable $e) {
            // Index missing — ignore.
        }
    }
};
