<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    public function up(): void
    {
        Schema::connection('mysql_admin')->create('admin_agreement_assignable_modules', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 80)->unique();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('mysql_admin')->create('admin_agreement_placeholders', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 80)->index();
            $table->string('placeholder', 80);
            $table->string('description')->nullable();
            $table->string('group_label', 80)->nullable();
            $table->string('source_key', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['module_key', 'placeholder'], 'admin_agreement_placeholders_module_token_unique');
        });

        $this->seedAssignableModules();
        $this->seedPlaceholders();
        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::connection('mysql_admin')->dropIfExists('admin_agreement_placeholders');
        Schema::connection('mysql_admin')->dropIfExists('admin_agreement_assignable_modules');

        $names = ['agreement_settings_view', 'agreement_settings_edit'];
        $ids = DB::connection('mysql_admin')->table('admin_permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::connection('mysql_admin')->table('admin_role_has_permissions')->whereIn('admin_permission_id', $ids)->delete();
            DB::connection('mysql_admin')->table('admin_model_has_permissions')->whereIn('admin_permission_id', $ids)->delete();
            DB::connection('mysql_admin')->table('admin_permissions')->whereIn('id', $ids)->delete();
        }
    }

    private function seedAssignableModules(): void
    {
        // Uses current erp_modules (includes bike_on_rent, garages_customers, customer_invoices).
        $excluded = [
            'dashboard',
            'recycle_bin',
            'agreements',
            'documents',
            'accounts',
            'vouchers',
            'vat',
        ];
        $keys = array_values(array_diff(array_keys(config('erp_modules.modules', [])), $excluded));
        $now = now();
        foreach ($keys as $i => $key) {
            DB::connection('mysql_admin')->table('admin_agreement_assignable_modules')->updateOrInsert(
                ['module_key' => $key],
                [
                    'enabled' => true,
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedPlaceholders(): void
    {
        $now = now();
        $rows = [
            ['module_key' => 'riders', 'placeholder' => '{rider_name}', 'description' => 'Rider full name', 'group_label' => 'Personal Information', 'source_key' => 'name', 'sort_order' => 1],
            ['module_key' => 'riders', 'placeholder' => '{rider_code}', 'description' => 'Rider ID / code', 'group_label' => 'Personal Information', 'source_key' => 'rider_id', 'sort_order' => 2],
            ['module_key' => 'riders', 'placeholder' => '{rider_email}', 'description' => 'Email address', 'group_label' => 'Personal Information', 'source_key' => 'email', 'sort_order' => 3],
            ['module_key' => 'riders', 'placeholder' => '{rider_phone}', 'description' => 'Phone number', 'group_label' => 'Personal Information', 'source_key' => 'personal_contact', 'sort_order' => 4],
            ['module_key' => 'riders', 'placeholder' => '{rider_cnic}', 'description' => 'CNIC / national ID', 'group_label' => 'Personal Information', 'source_key' => 'emirate_id', 'sort_order' => 5],
            ['module_key' => 'riders', 'placeholder' => '{rider_passport_number}', 'description' => 'Passport number', 'group_label' => 'Personal Information', 'source_key' => 'passport', 'sort_order' => 6],
            ['module_key' => 'riders', 'placeholder' => '{rider_nationality}', 'description' => 'Nationality', 'group_label' => 'Personal Information', 'source_key' => 'nationality', 'sort_order' => 7],
            ['module_key' => 'riders', 'placeholder' => '{rider_date_of_birth}', 'description' => 'Date of birth', 'group_label' => 'Personal Information', 'source_key' => 'dob', 'sort_order' => 8],
            ['module_key' => 'riders', 'placeholder' => '{rider_gender}', 'description' => 'Gender', 'group_label' => 'Personal Information', 'source_key' => 'ethnicity', 'sort_order' => 9],
            ['module_key' => 'riders', 'placeholder' => '{rider_address}', 'description' => 'Address', 'group_label' => 'Address Information', 'source_key' => 'address', 'sort_order' => 10],
            ['module_key' => 'riders', 'placeholder' => '{rider_city}', 'description' => 'City', 'group_label' => 'Address Information', 'source_key' => 'city', 'sort_order' => 11],
            ['module_key' => 'riders', 'placeholder' => '{rider_country}', 'description' => 'Country', 'group_label' => 'Address Information', 'source_key' => 'country', 'sort_order' => 12],
            ['module_key' => 'riders', 'placeholder' => '{joining_date}', 'description' => 'Date of joining', 'group_label' => 'Employment Information', 'source_key' => 'doj', 'sort_order' => 13],
            ['module_key' => 'riders', 'placeholder' => '{designation}', 'description' => 'Job designation', 'group_label' => 'Employment Information', 'source_key' => 'designation', 'sort_order' => 14],
            ['module_key' => 'riders', 'placeholder' => '{salary}', 'description' => 'Salary', 'group_label' => 'Employment Information', 'source_key' => 'salary', 'sort_order' => 15],
            ['module_key' => 'employees', 'placeholder' => '{rider_name}', 'description' => 'Employee full name', 'group_label' => 'Personal Information', 'source_key' => 'name', 'sort_order' => 1],
            ['module_key' => 'employees', 'placeholder' => '{rider_code}', 'description' => 'Employee ID / code', 'group_label' => 'Personal Information', 'source_key' => 'employee_id', 'sort_order' => 2],
            ['module_key' => 'employees', 'placeholder' => '{rider_email}', 'description' => 'Email address', 'group_label' => 'Personal Information', 'source_key' => 'company_email', 'sort_order' => 3],
            ['module_key' => 'employees', 'placeholder' => '{rider_phone}', 'description' => 'Phone number', 'group_label' => 'Personal Information', 'source_key' => 'company_contact', 'sort_order' => 4],
            ['module_key' => 'employees', 'placeholder' => '{rider_cnic}', 'description' => 'Emirates ID', 'group_label' => 'Personal Information', 'source_key' => 'emirate_id', 'sort_order' => 5],
            ['module_key' => 'employees', 'placeholder' => '{rider_passport_number}', 'description' => 'Passport number', 'group_label' => 'Personal Information', 'source_key' => 'passport', 'sort_order' => 6],
            ['module_key' => 'employees', 'placeholder' => '{joining_date}', 'description' => 'Date of joining', 'group_label' => 'Employment Information', 'source_key' => 'doj', 'sort_order' => 13],
            ['module_key' => 'employees', 'placeholder' => '{designation}', 'description' => 'Job designation', 'group_label' => 'Employment Information', 'source_key' => 'designation', 'sort_order' => 14],
            ['module_key' => 'employees', 'placeholder' => '{salary}', 'description' => 'Salary', 'group_label' => 'Employment Information', 'source_key' => 'salary', 'sort_order' => 15],
            ['module_key' => 'system', 'placeholder' => '{company_name}', 'description' => 'Company name', 'group_label' => 'System Information', 'source_key' => 'company_name', 'sort_order' => 1],
            ['module_key' => 'system', 'placeholder' => '{current_date}', 'description' => "Today's date", 'group_label' => 'System Information', 'source_key' => 'current_date', 'sort_order' => 2],
        ];

        foreach ($rows as $row) {
            DB::connection('mysql_admin')->table('admin_agreement_placeholders')->updateOrInsert(
                ['module_key' => $row['module_key'], 'placeholder' => $row['placeholder']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    private function seedPermissions(): void
    {
        $now = now();
        $names = ['agreement_settings_view', 'agreement_settings_edit'];
        foreach ($names as $name) {
            DB::connection('mysql_admin')->table('admin_permissions')->updateOrInsert(
                ['name' => $name],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $superAdminRoleId = DB::connection('mysql_admin')->table('admin_roles')->where('name', 'Super Admin')->value('id');
        if (! $superAdminRoleId) {
            return;
        }

        $permissionIds = DB::connection('mysql_admin')->table('admin_permissions')->whereIn('name', $names)->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::connection('mysql_admin')->table('admin_role_has_permissions')->updateOrInsert(
                [
                    'admin_role_id' => $superAdminRoleId,
                    'admin_permission_id' => $permissionId,
                ],
                []
            );
        }
    }
};
