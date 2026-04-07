<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_custom_fields')) {
            return;
        }

        Schema::table('account_custom_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('account_custom_fields', 'help_text')) {
                $table->text('help_text')->nullable()->after('label');
            }
            if (!Schema::hasColumn('account_custom_fields', 'data_privacy')) {
                $table->json('data_privacy')->nullable()->after('help_text'); // ['pii' => bool, 'ephi' => bool]
            }
            if (!Schema::hasColumn('account_custom_fields', 'prevent_duplicate_values')) {
                $table->boolean('prevent_duplicate_values')->default(false)->after('data_privacy');
            }
            if (!Schema::hasColumn('account_custom_fields', 'default_value')) {
                $table->string('default_value', 500)->nullable()->after('prevent_duplicate_values');
            }
            if (!Schema::hasColumn('account_custom_fields', 'input_format')) {
                $table->string('input_format', 100)->nullable()->after('default_value');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('account_custom_fields')) {
            return;
        }

        Schema::table('account_custom_fields', function (Blueprint $table) {
            $dropColumns = collect(['help_text', 'data_privacy', 'prevent_duplicate_values', 'default_value', 'input_format'])
                ->filter(fn ($column) => Schema::hasColumn('account_custom_fields', $column))
                ->values()
                ->all();

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
