<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_messages', 'media_id')) {
                $table->string('media_id', 191)->nullable()->after('body');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_mime')) {
                $table->string('media_mime', 128)->nullable()->after('media_id');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_filename')) {
                $table->string('media_filename', 255)->nullable()->after('media_mime');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_path')) {
                $table->string('media_path', 512)->nullable()->after('media_filename');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_size')) {
                $table->unsignedBigInteger('media_size')->nullable()->after('media_path');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_sha256')) {
                $table->string('media_sha256', 64)->nullable()->after('media_size');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_cached_at')) {
                $table->timestamp('media_cached_at')->nullable()->after('media_sha256');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            foreach ([
                'media_id',
                'media_mime',
                'media_filename',
                'media_path',
                'media_size',
                'media_sha256',
                'media_cached_at',
            ] as $column) {
                if (Schema::hasColumn('whatsapp_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
