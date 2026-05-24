<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VISA_INFO_FIELDS = [
        'license_no',
        'license_expiry',
        'road_permit',
        'road_permit_expiry',
    ];

    private const LABOR_INFO_FIELDS = [
        'person_code',
        'labor_card_number',
        'labor_card_expiry',
        'wps',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'license_no')) {
                $table->string('license_no', 191)->nullable()->after('visa_expiry');
            }
            if (! Schema::hasColumn('employees', 'license_expiry')) {
                $table->date('license_expiry')->nullable()->after('license_no');
            }
            if (! Schema::hasColumn('employees', 'road_permit')) {
                $table->string('road_permit', 255)->nullable()->after('license_expiry');
            }
            if (! Schema::hasColumn('employees', 'road_permit_expiry')) {
                $table->date('road_permit_expiry')->nullable()->after('road_permit');
            }
            if (! Schema::hasColumn('employees', 'person_code')) {
                $table->string('person_code', 50)->nullable()->after('road_permit_expiry');
            }
            if (! Schema::hasColumn('employees', 'labor_card_number')) {
                $table->string('labor_card_number', 100)->nullable()->after('person_code');
            }
            if (! Schema::hasColumn('employees', 'labor_card_expiry')) {
                $table->date('labor_card_expiry')->nullable()->after('labor_card_number');
            }
            if (! Schema::hasColumn('employees', 'wps')) {
                $table->string('wps', 100)->nullable()->after('labor_card_expiry');
            }
        });

        $this->ensureLaborInfoCategory();
        $this->seedFieldAssignments();
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        $columns = array_merge(self::VISA_INFO_FIELDS, self::LABOR_INFO_FIELDS);
        $existing = array_filter($columns, fn (string $col) => Schema::hasColumn('employees', $col));

        if ($existing !== []) {
            Schema::table('employees', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }

        if (Schema::hasTable('employee_field_category_assignments')) {
            DB::table('employee_field_category_assignments')
                ->whereIn('field_key', $columns)
                ->delete();
        }
    }

    private function ensureLaborInfoCategory(): void
    {
        if (! Schema::hasTable('employee_categories')) {
            return;
        }

        DB::table('employee_categories')->updateOrInsert(
            ['slug' => 'labor_info'],
            [
                'label' => 'Labor Info',
                'display_order' => 3,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('employee_categories')
            ->where('slug', 'additional_info')
            ->update(['display_order' => 4, 'updated_at' => now()]);

        DB::table('employee_categories')
            ->where('slug', 'other')
            ->update(['display_order' => 5, 'updated_at' => now()]);
    }

    private function seedFieldAssignments(): void
    {
        if (! Schema::hasTable('employee_field_category_assignments')
            || ! Schema::hasTable('employee_categories')) {
            return;
        }

        $slugToId = DB::table('employee_categories')->whereNotNull('slug')->pluck('id', 'slug')->all();
        $visaCategoryId = $slugToId['visa_info'] ?? null;
        $laborCategoryId = $slugToId['labor_info'] ?? null;

        if ($visaCategoryId === null && $laborCategoryId === null) {
            return;
        }

        $maxOrder = (int) DB::table('employee_field_category_assignments')->max('display_order');
        $now = now();

        foreach (self::VISA_INFO_FIELDS as $fieldKey) {
            if (! Schema::hasColumn('employees', $fieldKey) || $visaCategoryId === null) {
                continue;
            }
            if (DB::table('employee_field_category_assignments')->where('field_key', $fieldKey)->exists()) {
                continue;
            }
            DB::table('employee_field_category_assignments')->insert([
                'field_key' => $fieldKey,
                'category_id' => $visaCategoryId,
                'display_order' => ++$maxOrder,
                'is_visible' => true,
                'is_required' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (self::LABOR_INFO_FIELDS as $fieldKey) {
            if (! Schema::hasColumn('employees', $fieldKey) || $laborCategoryId === null) {
                continue;
            }
            if (DB::table('employee_field_category_assignments')->where('field_key', $fieldKey)->exists()) {
                continue;
            }
            DB::table('employee_field_category_assignments')->insert([
                'field_key' => $fieldKey,
                'category_id' => $laborCategoryId,
                'display_order' => ++$maxOrder,
                'is_visible' => true,
                'is_required' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
