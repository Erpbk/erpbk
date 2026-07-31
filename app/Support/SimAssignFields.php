<?php

namespace App\Support;

use App\Models\SimAssignFieldAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SimAssignFields
{
    public static function humanizeFieldKey(string $key): string
    {
        return match ($key) {
            'doj' => 'Date of Joining',
            default => ucwords(str_replace(['_', '-'], ' ', $key)),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaultAssignFieldCatalog(): array
    {
        return [
            ['field_key' => 'number', 'kind' => 'virtual', 'display_label' => 'Number', 'input_type' => 'text', 'display_order' => 0, 'show_on_active' => true, 'show_on_change' => true, 'is_required' => false, 'input_config' => ['readonly' => true]],
            ['field_key' => 'assign_to_display', 'kind' => 'virtual', 'display_label' => 'Assigned to', 'input_type' => 'text', 'display_order' => 1, 'show_on_active' => false, 'show_on_change' => true, 'is_required' => false, 'input_config' => ['readonly' => true]],
            ['field_key' => 'assignee_type', 'kind' => 'virtual', 'display_label' => 'User type', 'input_type' => 'select', 'display_order' => 2, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => true, 'input_config' => ['assign_options' => ['rider' => 'Rider', 'employee' => 'Employee']]],
            ['field_key' => 'assign_to_rider', 'kind' => 'virtual', 'display_label' => 'Assign to rider', 'input_type' => 'select', 'display_order' => 3, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => ['assign_group' => 'rider']],
            ['field_key' => 'assign_to_employee', 'kind' => 'virtual', 'display_label' => 'Assign to employee', 'input_type' => 'select', 'display_order' => 4, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => false, 'input_config' => ['assign_group' => 'employee']],
            ['field_key' => 'note_date', 'kind' => 'virtual', 'display_label' => 'Assign date', 'input_type' => 'date', 'display_order' => 5, 'show_on_active' => true, 'show_on_change' => false, 'is_required' => true],
            ['field_key' => 'return_date', 'kind' => 'virtual', 'display_label' => 'Return date', 'input_type' => 'date', 'display_order' => 6, 'show_on_active' => false, 'show_on_change' => true, 'is_required' => true],
            ['field_key' => 'notes', 'kind' => 'virtual', 'display_label' => 'Notes', 'input_type' => 'textarea', 'display_order' => 7, 'show_on_active' => true, 'show_on_change' => true, 'is_required' => false],
        ];
    }

    public static function syncSimAssignFieldAssignments(): void
    {
        if (!Schema::hasTable('sim_assign_field_assignments')) {
            return;
        }

        foreach (self::defaultAssignFieldCatalog() as $def) {
            $key = $def['field_key'];

            $assignment = SimAssignFieldAssignment::query()
                ->where('field_key', $key)
                ->first();

            if ($assignment) {
                continue;
            }

            SimAssignFieldAssignment::query()->create([
                'field_key' => $key,
                'kind' => $def['kind'],
                'display_label' => $def['display_label'],
                'input_type' => $def['input_type'] ?? null,
                'input_config' => $def['input_config'] ?? null,
                'display_order' => $def['display_order'] ?? 0,
                'is_visible' => true,
                'is_required' => $def['is_required'] ?? false,
                'show_on_active' => $def['show_on_active'] ?? false,
                'show_on_change' => $def['show_on_change'] ?? false,
            ]);
        }
    }

    /**
     * @return Collection<int, SimAssignFieldAssignment>
     */
    public static function assignModalFields(string $context): Collection
    {
        if (!Schema::hasTable('sim_assign_field_assignments')) {
            return collect();
        }

        self::syncSimAssignFieldAssignments();

        $query = SimAssignFieldAssignment::query()
            ->with('customField')
            ->orderBy('display_order')
            ->orderBy('id');

        if ($context === 'return' || $context === 'change') {
            $query->where('show_on_change', true);
        } else {
            $query->where('show_on_active', true);
        }

        return $query->get();
    }

    /**
     * Custom field IDs used only on SIM assign/return modals.
     *
     * @return list<int>
     */
    public static function assignOnlyCustomFieldIds(): array
    {
        if (!Schema::hasTable('sim_assign_field_assignments')) {
            return [];
        }

        return SimAssignFieldAssignment::query()
            ->whereNotNull('custom_field_id')
            ->pluck('custom_field_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
