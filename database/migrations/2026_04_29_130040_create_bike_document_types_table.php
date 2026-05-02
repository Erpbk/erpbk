<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bike_document_types')) {
            Schema::create('bike_document_types', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique()->comment('Slug used to match uploaded file names');
                $table->string('label', 255)->nullable();
                $table->string('type', 20)->default('single')->comment('single or dual');
                $table->string('front_label', 255)->nullable();
                $table->string('back_label', 255)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $defaults = [
            ['key' => 'mulkiya', 'label' => 'Mulkiya', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 0],
            ['key' => 'insurance', 'label' => 'Bike Insurance', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 1],
            ['key' => 'advertising', 'label' => 'Advertising Permit', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 2],
            // Optional generic agreement/contract doc for settings parity
            ['key' => 'contract', 'label' => 'Agreement/Contract', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 3],
        ];

        $now = now();
        foreach ($defaults as $row) {
            DB::table('bike_document_types')->updateOrInsert(
                ['key' => $row['key']],
                array_merge($row, [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_document_types');
    }
};

