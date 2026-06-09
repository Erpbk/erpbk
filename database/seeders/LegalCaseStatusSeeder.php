<?php

namespace Database\Seeders;

use App\Models\LegalCaseStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class LegalCaseStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('legal_case_statuses')) {
            $this->command?->warn('Skipping LegalCaseStatusSeeder: table legal_case_statuses does not exist yet.');
            return;
        }

        $statuses = [
            ['name' => 'Case Filed', 'code' => 'CF', 'description' => 'Initial case filing', 'category' => 'Document', 'is_required' => true, 'display_order' => 1],
            ['name' => 'Court Hearing', 'code' => 'CH', 'description' => 'Scheduled court hearing', 'category' => 'Other', 'is_required' => false, 'display_order' => 2],
            ['name' => 'Documentation', 'code' => 'DOC', 'description' => 'Required documentation submission', 'category' => 'Document', 'is_required' => true, 'display_order' => 3],
            ['name' => 'Legal Review', 'code' => 'LR', 'description' => 'Under legal review', 'category' => 'Other', 'is_required' => false, 'display_order' => 4],
            ['name' => 'Case Closed', 'code' => 'CC', 'description' => 'Case closure step', 'category' => 'Other', 'is_required' => true, 'display_order' => 5],
        ];

        foreach ($statuses as $status) {
            LegalCaseStatus::updateOrCreate(
                ['name' => $status['name']],
                [
                    'code' => $status['code'],
                    'description' => $status['description'],
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
