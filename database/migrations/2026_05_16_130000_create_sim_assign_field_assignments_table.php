<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sim_assign_field_assignments')) {
            return;
        }

        Schema::create('sim_assign_field_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('field_key', 80)->nullable();
            $table->unsignedBigInteger('custom_field_id')->nullable();
            $table->string('kind', 20)->default('virtual');
            $table->string('display_label', 255)->nullable();
            $table->string('input_type', 50)->nullable();
            $table->json('input_config')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('show_on_active')->default(true)->comment('Assign SIM modal');
            $table->boolean('show_on_change')->default(false)->comment('Return SIM modal');
            $table->timestamps();

            $table->unique('field_key');
            $table->unique('custom_field_id');
            $table->foreign('custom_field_id')
                ->references('id')
                ->on('module_custom_fields')
                ->cascadeOnDelete();
        });

        $defaults = [
            ['field_key' => 'number', 'kind' => 'virtual', 'display_label' => 'Number', 'input_type' => 'text', 'display_order' => 0, 'show_on_active' => true, 'show_on_change' => true, 'is_required' => false, 'input_config' => json_encode(['readonly' => true])],
            ['field_key' => 'assignee_type', 'kind' => 'virtual', 'display_label' => 'User type', 'input_type' => 'select', 'display_order' => 1, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => true, 'input_config' => json_encode(['assign_options' => ['rider' => 'Rider', 'employee' => 'Employee']])],
            ['field_key' => 'assign_to_rider', 'kind' => 'virtual', 'display_label' => 'Assign to rider', 'input_type' => 'select', 'display_order' => 2, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => json_encode(['assign_group' => 'rider'])],
            ['field_key' => 'assign_to_employee', 'kind' => 'virtual', 'display_label' => 'Assign to employee', 'input_type' => 'select', 'display_order' => 3, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => json_encode(['assign_group' => 'employee'])],
            ['field_key' => 'note_date', 'kind' => 'virtual', 'display_label' => 'Assign date', 'input_type' => 'date', 'display_order' => 4, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => true],
            ['field_key' => 'return_date', 'kind' => 'virtual', 'display_label' => 'Return date', 'input_type' => 'date', 'display_order' => 5, 'show_on_active' => false, 'show_on_change' => true, 'is_required' => true],
            ['field_key' => 'notes', 'kind' => 'virtual', 'display_label' => 'Notes', 'input_type' => 'textarea', 'display_order' => 6, 'show_on_active' => true, 'show_on_change' => true, 'is_required' => false],
        ];

        foreach ($defaults as $row) {
            DB::table('sim_assign_field_assignments')->insert(array_merge($row, [
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_assign_field_assignments');
    }
};
