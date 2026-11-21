<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_template_bindings')) {
            Schema::create('whatsapp_template_bindings', function (Blueprint $table) {
                $table->id();
                $table->string('trigger_key')->unique();
                $table->unsignedBigInteger('whatsapp_template_id');
                $table->unsignedBigInteger('whatsapp_connection_id')->nullable();
                $table->timestamps();

                $table->index('whatsapp_template_id');
            });
        }

        // Seed binding for absent_notification → rider_activity:abs when template exists.
        $templateId = DB::table('whatsapp_templates')
            ->where('name', 'absent_notification')
            ->where('language', 'en')
            ->where('status', 'active')
            ->value('id');

        if ($templateId && Schema::hasTable('whatsapp_template_bindings')) {
            $exists = DB::table('whatsapp_template_bindings')
                ->where('trigger_key', 'rider_activity:abs')
                ->exists();
            if (! $exists) {
                DB::table('whatsapp_template_bindings')->insert([
                    'trigger_key' => 'rider_activity:abs',
                    'whatsapp_template_id' => $templateId,
                    'whatsapp_connection_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_template_bindings');
    }
};
