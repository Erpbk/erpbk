<?php

namespace Database\Seeders;

use App\Models\LicenseStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class LicenseStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('license_statuses')) {
            $this->command?->warn('Skipping LicenseStatusSeeder: table license_statuses does not exist yet.');
            return;
        }

        $statuses = [
            [
                'name' => 'RTA File Opening',
                'code' => 'RFO',
                'description' => 'Open driving license file at RTA / driving institute',
                'default_fee' => 200.00,
                'category' => 'Document',
                'is_required' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Learning Permit',
                'code' => 'LP',
                'description' => 'Issue learner permit for motorcycle / light vehicle',
                'default_fee' => 450.00,
                'category' => 'Permit',
                'is_required' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Theory Test (Knowledge Test)',
                'code' => 'TT',
                'description' => 'RTA knowledge / theory test fee',
                'default_fee' => 200.00,
                'category' => 'License',
                'is_required' => true,
                'display_order' => 3,
            ],
            [
                'name' => 'Theory Test Retake',
                'code' => 'TTR',
                'description' => 'Retake fee for failed theory test',
                'default_fee' => 200.00,
                'category' => 'License',
                'is_required' => false,
                'display_order' => 4,
            ],
            [
                'name' => 'Eye Test / Medical Fitness',
                'code' => 'ETM',
                'description' => 'Vision screening and medical fitness for license',
                'default_fee' => 150.00,
                'category' => 'Other',
                'is_required' => true,
                'display_order' => 5,
            ],
            [
                'name' => 'Driving Classes',
                'code' => 'DC',
                'description' => 'Practical training sessions at driving institute',
                'default_fee' => 1200.00,
                'category' => 'License',
                'is_required' => true,
                'display_order' => 6,
            ],
            [
                'name' => 'Yard Test (Internal Assessment)',
                'code' => 'YT',
                'description' => 'Internal yard / parking assessment before RTA road test',
                'default_fee' => 250.00,
                'category' => 'License',
                'is_required' => true,
                'display_order' => 7,
            ],
            [
                'name' => 'RTA Road Test',
                'code' => 'RRT',
                'description' => 'Final RTA road test fee',
                'default_fee' => 300.00,
                'category' => 'License',
                'is_required' => true,
                'display_order' => 8,
            ],
            [
                'name' => 'Road Test Retake',
                'code' => 'RTR',
                'description' => 'Retake fee for failed RTA road test',
                'default_fee' => 300.00,
                'category' => 'License',
                'is_required' => false,
                'display_order' => 9,
            ],
            [
                'name' => 'License Issuance Fee',
                'code' => 'LIF',
                'description' => 'Final driving license issuance at RTA',
                'default_fee' => 420.00,
                'category' => 'License',
                'is_required' => true,
                'display_order' => 10,
            ],
            [
                'name' => 'Knowledge & Innovation Fee',
                'code' => 'KIF',
                'description' => 'Dubai government knowledge and innovation fee',
                'default_fee' => 20.00,
                'category' => 'Other',
                'is_required' => true,
                'display_order' => 11,
            ],
            [
                'name' => 'Golden Chance (Direct Test)',
                'code' => 'GC',
                'description' => 'Golden chance direct road test without full training',
                'default_fee' => 600.00,
                'category' => 'License',
                'is_required' => false,
                'display_order' => 12,
            ],
            [
                'name' => 'File Renewal',
                'code' => 'FR',
                'description' => 'Renew expired driving license file at RTA',
                'default_fee' => 300.00,
                'category' => 'Document',
                'is_required' => false,
                'display_order' => 13,
            ],
            [
                'name' => 'License Amendment / Category Change',
                'code' => 'LAC',
                'description' => 'Change license category or amend file details',
                'default_fee' => 350.00,
                'category' => 'License',
                'is_required' => false,
                'display_order' => 14,
            ],
            [
                'name' => 'RTA Violation / Fine',
                'code' => 'RVF',
                'description' => 'RTA violations or fines during licensing process',
                'default_fee' => 100.00,
                'category' => 'Other',
                'is_required' => false,
                'display_order' => 15,
            ],
        ];

        foreach ($statuses as $status) {
            LicenseStatus::updateOrCreate(
                ['name' => $status['name']],
                [
                    'code' => $status['code'],
                    'description' => $status['description'],
                    'default_fee' => $status['default_fee'],
                    'category' => $status['category'],
                    'is_required' => $status['is_required'],
                    'display_order' => $status['display_order'],
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );
        }
    }
}
