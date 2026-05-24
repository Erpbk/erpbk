<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_categories')) {
            Schema::create('employee_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('slug', 50)->nullable()->unique();
                $table->string('label');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });

            $defaults = [
                ['slug' => 'employee_info', 'label' => 'Employee Info', 'display_order' => 0, 'is_system' => true],
                ['slug' => 'visa_info', 'label' => 'Visa Info', 'display_order' => 1, 'is_system' => true],
                ['slug' => 'employment_info', 'label' => 'Employment Info', 'display_order' => 2, 'is_system' => true],
                ['slug' => 'additional_info', 'label' => 'Additional Information', 'display_order' => 3, 'is_system' => true],
                ['slug' => 'other', 'label' => 'Other', 'display_order' => 4, 'is_system' => true],
            ];

            foreach ($defaults as $row) {
                DB::table('employee_categories')->updateOrInsert(
                    ['slug' => $row['slug']],
                    array_merge($row, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        }

        if (!Schema::hasTable('employee_custom_fields')) {
            Schema::create('employee_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->string('help_text')->nullable();
                $table->json('data_privacy')->nullable();
                $table->boolean('prevent_duplicate_values')->default(false);
                $table->string('default_value', 500)->nullable();
                $table->string('input_format', 100)->nullable();
                $table->string('data_type', 50);
                $table->boolean('is_mandatory')->default(false);
                $table->boolean('is_visible')->default(true);
                $table->json('config')->nullable();
                $table->foreignId('category_id')->nullable()->constrained('employee_categories')->cascadeOnDelete();
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('employee_field_category_assignments')) {
            Schema::create('employee_field_category_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('field_key', 80)->unique();
                $table->string('display_label')->nullable();
                $table->string('input_type', 50)->nullable();
                $table->json('input_config')->nullable();
                $table->foreignId('category_id')->constrained('employee_categories')->cascadeOnDelete();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_required')->default(false);
                $table->timestamps();
            });

            $slugMap = [
                'employee_info' => [
                    'name', 'employee_id', 'company_email', 'personal_email', 'personal_contact',
                    'company_contact', 'emergency_contact', 'dob', 'address', 'profile_image',
                ],
                'visa_info' => [
                    'emirate_id', 'emirate_expiry', 'passport', 'passport_expiry',
                    'visa_sponsor', 'visa_occupation', 'visa_expiry',
                ],
                'employment_info' => [
                    'nationality_id', 'department_id', 'designation', 'salary', 'branch_id', 'doj', 'status',
                ],
                'additional_info' => ['notes', 'custom_field_values'],
            ];

            $slugToId = DB::table('employee_categories')->whereNotNull('slug')->pluck('id', 'slug')->all();
            $otherCategoryId = $slugToId['other'] ?? array_values($slugToId)[0] ?? null;
            $order = 0;
            $now = now();

            foreach ($slugMap as $slug => $keys) {
                $categoryId = $slugToId[$slug] ?? $otherCategoryId;
                if ($categoryId === null) {
                    continue;
                }
                foreach ($keys as $fieldKey) {
                    if (!Schema::hasColumn('employees', $fieldKey)) {
                        continue;
                    }
                    DB::table('employee_field_category_assignments')->updateOrInsert(
                        ['field_key' => $fieldKey],
                        [
                            'category_id' => $categoryId,
                            'display_order' => $order++,
                            'is_visible' => true,
                            'is_required' => false,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }

        if (!Schema::hasTable('employee_document_types')) {
            Schema::create('employee_document_types', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique();
                $table->string('label', 255)->nullable();
                $table->string('type', 20)->default('single');
                $table->string('front_label', 255)->nullable();
                $table->string('back_label', 255)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            $defaults = [
                ['key' => 'photo', 'label' => 'Profile Photo', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 0],
                ['key' => 'passport', 'label' => null, 'type' => 'dual', 'front_label' => 'Passport ( First Page )', 'back_label' => 'Passport ( Second Page )', 'display_order' => 1],
                ['key' => 'emirates', 'label' => null, 'type' => 'dual', 'front_label' => 'Emirates ID ( Front )', 'back_label' => 'Emirates ID ( Back )', 'display_order' => 2],
                ['key' => 'contract', 'label' => 'Employment Contract', 'type' => 'single', 'front_label' => null, 'back_label' => null, 'display_order' => 3],
            ];

            $now = now();
            foreach ($defaults as $row) {
                DB::table('employee_document_types')->updateOrInsert(
                    ['key' => $row['key']],
                    array_merge($row, ['is_active' => true, 'created_at' => $now, 'updated_at' => $now])
                );
            }
        }

        if (!Schema::hasTable('employee_top_categories')) {
            Schema::create('employee_top_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('name');
                $table->string('employee_column', 80)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_in_top_bar')->default(true);
                $table->boolean('show_in_view_cards')->default(false);
                $table->timestamps();
                $table->index(['company_id', 'employee_column'], 'idx_employee_top_categories_company_column');
            });
        }

        if (!Schema::hasTable('employee_top_options')) {
            Schema::create('employee_top_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('category_id')->constrained('employee_top_categories')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_in_top_bar')->default(true);
                $table->boolean('show_in_view_cards')->default(true);
                $table->timestamps();
            });
        }

        // Seed default Employee Status category (maps to employees.status column).
        if (Schema::hasTable('employee_top_categories')) {
            DB::table('employee_top_categories')->updateOrInsert(
                ['employee_column' => 'status'],
                [
                    'name' => 'Employee Status',
                    'display_order' => 0,
                    'is_active' => true,
                    'show_in_top_bar' => true,
                    'show_in_view_cards' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_top_options');
        Schema::dropIfExists('employee_top_categories');
        Schema::dropIfExists('employee_document_types');
        Schema::dropIfExists('employee_field_category_assignments');
        Schema::dropIfExists('employee_custom_fields');
        Schema::dropIfExists('employee_categories');
    }
};
