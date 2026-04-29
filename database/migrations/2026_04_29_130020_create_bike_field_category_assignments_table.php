<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bike_field_category_assignments')) {
            Schema::create('bike_field_category_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('field_key', 80)->unique()->comment('Bike table column name');
                $table->foreignId('category_id')->constrained('bike_categories')->cascadeOnDelete();
                $table->unsignedInteger('display_order')->default(0);
                $table->string('display_label', 255)->nullable();
                $table->string('input_type', 50)->nullable()->comment('Optional override for fixed field input type');
                $table->json('input_config')->nullable()->comment('Optional JSON config for fixed field input');
                $table->boolean('is_visible')->default(true)->comment('When false, field is hidden from Bike add/edit/view');
                $table->boolean('is_required')->default(false)->comment('When true, field is required in Bike add/edit/view');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bikes')) {
            return;
        }

        $systemColumns = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'custom_field_values',
            'created_by',
            'updated_by',
            'deleted_by',
        ];

        $bikeColumns = Schema::getColumnListing('bikes');
        $fieldKeys = array_values(array_filter($bikeColumns, function ($col) use ($systemColumns) {
            return !in_array($col, $systemColumns, true);
        }));

        $slugToCategoryId = DB::table('bike_categories')->pluck('id', 'slug')->all();
        $otherId = (int) ($slugToCategoryId['other'] ?? 0);
        if ($otherId <= 0) {
            return;
        }

        $mapping = [
            'bike_info' => [
                'plate',
                'bike_code',
                'chassis_number',
                'engine',
                'vehicle_type',
                'model',
                'model_type',
                'color',
                'emirates',
                'branch_id',
                'company',
                'rider_id',
                'warehouse',
                'traffic_file_number',
                'registration_date',
                'expiry_date',
                'notes',
                'status',
                'customer_id',
            ],
            'insurance_info' => [
                'insurance_expiry',
                'insurance_co',
                'policy_no',
            ],
            'documents_info' => [
                'contract_number',
            ],
        ];

        $resolvedCategoryForField = [];
        foreach ($fieldKeys as $key) {
            $resolvedCategoryForField[$key] = $otherId;
            foreach ($mapping as $slug => $keys) {
                if (in_array($key, $keys, true)) {
                    $catId = (int) ($slugToCategoryId[$slug] ?? $otherId);
                    $resolvedCategoryForField[$key] = $catId > 0 ? $catId : $otherId;
                    break;
                }
            }
        }

        // Seed all fields with default assignments (order by column name).
        sort($fieldKeys);
        $displayOrderByCategory = [];
        // By default, fixed fields are NOT required.
        // Users decide requiredness from Bike Settings (bike_field_category_assignments.is_required).
        $requiredDefaults = [];
        foreach ($fieldKeys as $fieldKey) {
            $categoryId = (int) ($resolvedCategoryForField[$fieldKey] ?? $otherId);
            if (!isset($displayOrderByCategory[$categoryId])) {
                $displayOrderByCategory[$categoryId] = 0;
            }

            DB::table('bike_field_category_assignments')->updateOrInsert(
                ['field_key' => $fieldKey],
                [
                    'category_id' => $categoryId,
                    'display_order' => $displayOrderByCategory[$categoryId]++,
                    'display_label' => null,
                    'input_type' => null,
                    'input_config' => null,
                    'is_visible' => true,
                    'is_required' => in_array($fieldKey, $requiredDefaults, true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_field_category_assignments');
    }
};

