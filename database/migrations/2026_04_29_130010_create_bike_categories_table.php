<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bike_categories')) {
            Schema::create('bike_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->nullable()->unique()->comment('Used to map fixed bike fields; null for user-created categories');
                $table->string('label');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_system')->default(false)->comment('System categories cannot be deleted');
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->timestamps();
            });
        }

        $defaults = [
            ['slug' => 'bike_info', 'label' => 'Bike Info', 'display_order' => 0, 'is_system' => true],
            ['slug' => 'insurance_info', 'label' => 'Insurance Info', 'display_order' => 1, 'is_system' => true],
            ['slug' => 'documents_info', 'label' => 'Documents Info', 'display_order' => 2, 'is_system' => true],
            ['slug' => 'other', 'label' => 'Other', 'display_order' => 3, 'is_system' => true],
        ];

        foreach ($defaults as $row) {
            DB::table('bike_categories')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_categories');
    }
};

