<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cheque_categories')) {
            Schema::create('cheque_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('slug', 50)->nullable();
                $table->string('label');
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->timestamps();
                $table->unique(['company_id', 'slug'], 'cheque_categories_company_slug_unique');
            });
        }

        if (!Schema::hasTable('cheque_custom_fields')) {
            Schema::create('cheque_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
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
                $table->foreignId('category_id')->nullable()->constrained('cheque_categories')->nullOnDelete();
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cheque_field_category_assignments')) {
            Schema::create('cheque_field_category_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('field_key', 80);
                $table->foreignId('category_id')->constrained('cheque_categories')->cascadeOnDelete();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_required')->default(false);
                $table->string('display_label', 255)->nullable();
                $table->string('input_type', 50)->nullable();
                $table->json('input_config')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'field_key'], 'cheque_field_assignments_company_field_unique');
            });
        }

        if (Schema::hasTable('cheque_categories') && DB::table('cheque_categories')->count() === 0) {
            $defaults = [
                ['slug' => 'cheque_details', 'label' => 'Cheque Details', 'display_order' => 0, 'is_system' => true],
                ['slug' => 'parties', 'label' => 'Parties', 'display_order' => 1, 'is_system' => true],
                ['slug' => 'status_type', 'label' => 'Status & Type', 'display_order' => 2, 'is_system' => true],
                ['slug' => 'other', 'label' => 'Other', 'display_order' => 3, 'is_system' => true],
            ];
            foreach ($defaults as $row) {
                DB::table('cheque_categories')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (Schema::hasTable('cheque_field_category_assignments') && Schema::hasTable('cheque_categories')) {
            $slugMap = [
                'cheque_details' => ['cheque_number', 'amount', 'issue_date', 'cheque_date', 'billing_month', 'reference', 'description', 'attachment'],
                'parties' => ['payee_name', 'payer_name', 'payee_account', 'payer_account', 'bank_id', 'issued_by'],
                'status_type' => ['status', 'type', 'is_security'],
            ];
            $slugToId = DB::table('cheque_categories')->whereNotNull('slug')->pluck('id', 'slug')->all();
            $otherId = $slugToId['other'] ?? null;
            $order = 0;
            $now = now();
            foreach ($slugMap as $slug => $keys) {
                $categoryId = $slugToId[$slug] ?? $otherId;
                if (!$categoryId) {
                    continue;
                }
                foreach ($keys as $fieldKey) {
                    if (!Schema::hasTable('cheques') || !Schema::hasColumn('cheques', $fieldKey)) {
                        continue;
                    }
                    DB::table('cheque_field_category_assignments')->updateOrInsert(
                        ['field_key' => $fieldKey, 'company_id' => null],
                        [
                            'category_id' => $categoryId,
                            'display_order' => $order++,
                            'is_visible' => true,
                            'is_required' => in_array($fieldKey, ['cheque_number', 'amount', 'issue_date', 'type', 'status'], true),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_field_category_assignments');
        Schema::dropIfExists('cheque_custom_fields');
        Schema::dropIfExists('cheque_categories');
    }
};
