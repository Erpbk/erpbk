<?php

namespace Database\Seeders;

use App\Models\VisaStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VisaStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Job offer Letter',
                'code' => 'JOL',
                'description' => 'Job offer letter for visa processing',
                'default_fee' => 150.00,
                'category' => 'Document',
                'is_required' => true,
                'display_order' => 1
            ],
            [
                'name' => 'Labor Insurance',
                'code' => 'LI',
                'description' => 'Labor insurance for workers',
                'default_fee' => 300.00,
                'category' => 'Insurance',
                'is_required' => true,
                'display_order' => 2
            ],
            [
                'name' => 'Work Permit',
                'code' => 'WP',
                'description' => 'Permit to work legally',
                'default_fee' => 500.00,
                'category' => 'Permit',
                'is_required' => true,
                'display_order' => 3
            ],
            [
                'name' => 'Work Man Insurance',
                'code' => 'WMI',
                'description' => 'Insurance for workman compensation',
                'default_fee' => 250.00,
                'category' => 'Insurance',
                'is_required' => true,
                'display_order' => 4
            ],
            [
                'name' => 'Entry Permit (Inside)',
                'code' => 'EPI',
                'description' => 'Entry permit for those already inside the country',
                'default_fee' => 350.00,
                'category' => 'Permit',
                'is_required' => false,
                'display_order' => 5
            ],
            [
                'name' => 'Entry Permit (Outside)',
                'code' => 'EPO',
                'description' => 'Entry permit for those outside the country',
                'default_fee' => 400.00,
                'category' => 'Permit',
                'is_required' => false,
                'display_order' => 6
            ],
            [
                'name' => 'Status Change',
                'code' => 'SC',
                'description' => 'Change of visa status',
                'default_fee' => 200.00,
                'category' => 'Other',
                'is_required' => false,
                'display_order' => 7
            ],
            [
                'name' => 'Tawjeeh',
                'code' => 'TW',
                'description' => 'Tawjeeh service',
                'default_fee' => 100.00,
                'category' => 'Other',
                'is_required' => false,
                'display_order' => 8
            ],
            [
                'name' => 'Medical',
                'code' => 'MED',
                'description' => 'Medical examination for visa',
                'default_fee' => 320.00,
                'category' => 'Other',
                'is_required' => true,
                'display_order' => 9
            ],
            [
                'name' => 'Emirates ID + Residency',
                'code' => 'EIDR',
                'description' => 'Emirates ID and residency processing',
                'default_fee' => 600.00,
                'category' => 'Document',
                'is_required' => true,
                'display_order' => 10
            ],
            [
                'name' => 'Emirates ID',
                'code' => 'EID',
                'description' => 'Emirates ID processing only',
                'default_fee' => 300.00,
                'category' => 'Document',
                'is_required' => true,
                'display_order' => 11
            ],
            [
                'name' => 'Residency',
                'code' => 'RES',
                'description' => 'Residency processing only',
                'default_fee' => 400.00,
                'category' => 'Document',
                'is_required' => true,
                'display_order' => 12
            ],
            [
                'name' => 'Bike License',
                'code' => 'BL',
                'description' => 'License for bike operation',
                'default_fee' => 250.00,
                'category' => 'License',
                'is_required' => false,
                'display_order' => 13
            ],
            [
                'name' => 'Violation',
                'code' => 'VIO',
                'description' => 'Violation fees and penalties',
                'default_fee' => 100.00,
                'category' => 'Other',
                'is_required' => false,
                'display_order' => 14
            ],
            [
                'name' => 'Bed Space',
                'code' => 'BS',
                'description' => 'Accommodation bed space fee',
                'default_fee' => 200.00,
                'category' => 'Other',
                'is_required' => false,
                'display_order' => 15
            ],
        ];

        foreach ($statuses as $status) {
            VisaStatus::updateOrCreate(
                ['name' => $status['name']],
                [
                    'code' => $status['code'],
                    'description' => $status['description'],
                    'default_fee' => $status['default_fee'],
                    'category' => $status['category'],
                    'is_required' => $status['is_required'],
                    'display_order' => $status['display_order'],
                    'is_active' => true,
                    'created_by' => 1 // System user
                ]
            );
        }
    }
}
