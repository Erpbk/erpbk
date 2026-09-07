<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    public function up(): void
    {
        if (! Schema::connection('mysql_admin')->hasTable('admin_agreement_placeholders')) {
            return;
        }

        $conn = DB::connection('mysql_admin');

        // Drop removed logical / system sources.
        $conn->table('admin_agreement_placeholders')
            ->where('placeholder', '{agreement_date}')
            ->orWhereIn('source_key', [
                'agreement_date',
                'branch_name',
                'bike_number',
                'bike_model',
                'rider_name',
                'rider_code',
                'rider_email',
                'rider_phone',
                'rider_cnic',
                'rider_passport_number',
                'rider_nationality',
                'rider_date_of_birth',
                'rider_gender',
                'rider_address',
                'rider_city',
                'rider_country',
                'joining_date',
            ])
            ->delete();

        // Re-seed remapped module placeholders (DB source keys only) + system tokens.
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
            $conn->table('admin_agreement_placeholders')->updateOrInsert(
                ['module_key' => $row['module_key'], 'placeholder' => $row['placeholder']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // Ensure leftover relational placeholders are gone.
        $conn->table('admin_agreement_placeholders')
            ->whereIn('placeholder', ['{branch_name}', '{bike_number}', '{bike_model}', '{agreement_date}'])
            ->delete();
    }

    public function down(): void
    {
        // Non-reversible data cleanup; leave remapped rows in place.
    }
};
