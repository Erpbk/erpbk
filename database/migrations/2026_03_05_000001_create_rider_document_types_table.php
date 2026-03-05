<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rider_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique()->comment('Slug used to match file names (e.g. photo, passport)');
            $table->string('label', 255)->nullable()->comment('Display label for single document type');
            $table->string('type', 20)->default('single')->comment('single or dual');
            $table->string('front_label', 255)->nullable()->comment('Display label for dual type front/first page');
            $table->string('back_label', 255)->nullable()->comment('Display label for dual type back/second page');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaults = [
            ['key' => 'photo', 'label' => 'Profile Photo', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 0],
            ['key' => 'offer', 'label' => 'Job Offer Letter ( MOL )', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 1],
            ['key' => 'entry', 'label' => 'Entry Permit', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 2],
            ['key' => 'residency', 'label' => 'Residency', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 3],
            ['key' => 'health', 'label' => 'Health insurance', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 4],
            ['key' => 'workers', 'label' => 'Workers Compensation Insurance', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 5],
            ['key' => 'road', 'label' => 'Road Permit', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 6],
            ['key' => 'contract', 'label' => 'Agreement/Contract', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 7],
            ['key' => 'passport', 'label' => null, 'type' => 'dual', 'front_label' => 'Passport ( First Page )', 'back_label' => 'Passport ( Second Page )', 'display_order' => 8],
            ['key' => 'nic', 'label' => null, 'type' => 'dual', 'front_label' => 'Home Country NIC ( Front )', 'back_label' => 'Home Country NIC ( Back )', 'display_order' => 9],
            ['key' => 'labor', 'label' => null, 'type' => 'dual', 'front_label' => 'Labor Card ( Front )', 'back_label' => 'Labor Card ( Back )', 'display_order' => 10],
            ['key' => 'emirates', 'label' => null, 'type' => 'dual', 'front_label' => 'Emirates ID ( Front )', 'back_label' => 'Emirates ID ( Back )', 'display_order' => 11],
            ['key' => 'license', 'label' => null, 'type' => 'dual', 'front_label' => 'Bike License ( Front )', 'back_label' => 'Bike License ( Back )', 'display_order' => 12],
        ];

        $now = now();
        foreach ($defaults as $row) {
            DB::table('rider_document_types')->insert(array_merge($row, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_document_types');
    }
};
