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
        if (!Schema::hasTable('rider_categories')) {
            Schema::create('rider_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->nullable()->unique()->comment('Used to map fixed rider fields; null for user-created categories');
                $table->string('label');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_system')->default(false)->comment('System categories cannot be deleted');
                $table->timestamps();
            });
        }

        $defaults = [
            ['slug' => 'rider_info', 'label' => 'Rider Info', 'display_order' => 0, 'is_system' => true],
            ['slug' => 'visa_info', 'label' => 'Visa Info', 'display_order' => 1, 'is_system' => true],
            ['slug' => 'job_info', 'label' => 'Job Info', 'display_order' => 2, 'is_system' => true],
            ['slug' => 'labor_info', 'label' => 'Labor Info', 'display_order' => 3, 'is_system' => true],
            ['slug' => 'additional_info', 'label' => 'Additional Information', 'display_order' => 4, 'is_system' => true],
            ['slug' => 'other', 'label' => 'Other', 'display_order' => 5, 'is_system' => true],
        ];

        foreach ($defaults as $row) {
            DB::table('rider_categories')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_categories');
    }
};
