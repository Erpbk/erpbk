<?php

namespace Tests\Unit;

use App\Models\AdminAgreementPlaceholder;
use App\Models\Branch;
use App\Models\Riders;
use App\Services\Agreements\AgreementPlaceholderCatalog;
use App\Services\Agreements\AgreementPlaceholderResolver;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class AgreementPlaceholderCatalogTest extends TestCase
{
    public function test_source_field_options_are_system_plus_db_only_without_raw_fks(): void
    {
        $options = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptions('riders');

        $this->assertArrayHasKey('company_name', $options);
        $this->assertArrayHasKey('current_date', $options);
        $this->assertArrayNotHasKey('rider_name', $options);
        $this->assertArrayNotHasKey('agreement_date', $options);
        $this->assertArrayNotHasKey('branch_id', $options);
    }

    public function test_source_groups_include_related_branch_prefixed_fields(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('riders');
        $labels = array_column($groups, 'label');
        $this->assertContains('Related: Branch', $labels);

        $related = collect($groups)->firstWhere('label', 'Related: Branch');
        $this->assertIsArray($related);
        $this->assertNotEmpty($related['options']);
        $this->assertTrue(collect($related['options'])->keys()->contains(fn ($k) => str_starts_with((string) $k, 'branch.')));
    }

    public function test_system_source_options_exclude_module_db_fields(): void
    {
        $options = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptions('system');

        $this->assertSame([
            'company_name' => 'Company name',
            'current_date' => 'Current date',
        ], $options);
    }

    public function test_group_labels_are_predefined(): void
    {
        $labels = $this->app->make(AgreementPlaceholderCatalog::class)->groupLabels();

        $this->assertContains('Personal Information', $labels);
        $this->assertContains('Related', $labels);
        $this->assertContains('General', $labels);
    }

    public function test_grouped_for_module_groups_by_label(): void
    {
        $rows = new Collection([
            new AdminAgreementPlaceholder([
                'module_key' => 'riders',
                'placeholder' => '{rider_name}',
                'group_label' => 'Rider',
                'source_key' => 'name',
                'sort_order' => 1,
            ]),
            new AdminAgreementPlaceholder([
                'module_key' => 'system',
                'placeholder' => '{current_date}',
                'group_label' => 'System',
                'source_key' => 'current_date',
                'sort_order' => 2,
            ]),
        ]);

        $catalog = new class ($rows) extends AgreementPlaceholderCatalog {
            public function __construct(private Collection $rows)
            {
            }

            public function groupedForModule(?string $moduleKey): array
            {
                return $this->rows
                    ->groupBy(fn ($row) => $row->group_label ?: 'General')
                    ->all();
            }
        };

        $grouped = $catalog->groupedForModule('riders');

        $this->assertArrayHasKey('Rider', $grouped);
        $this->assertArrayHasKey('System', $grouped);
        $this->assertSame('{rider_name}', $grouped['Rider']->first()->placeholder);
    }

    public function test_admin_catalog_loads_seeded_module_placeholders(): void
    {
        $grouped = $this->app->make(AgreementPlaceholderCatalog::class)->groupedForModule('riders');

        $this->assertNotEmpty($grouped);
        $tokens = collect($grouped)->flatten()->pluck('placeholder')->all();
        $this->assertNotEmpty($tokens);
        $this->assertContains('{current_date}', $tokens);
        $this->assertNotContains('{agreement_date}', $tokens);
    }

    public function test_resolver_reads_dotted_relation_source(): void
    {
        $branch = new Branch(['name' => 'Downtown']);
        $branch->id = 9;

        $rider = new Riders();
        $rider->setRelation('branch', $branch);

        $resolver = $this->app->make(AgreementPlaceholderResolver::class);
        $method = new \ReflectionMethod($resolver, 'resolveSourceKey');
        $method->setAccessible(true);

        $value = $method->invoke($resolver, 'riders', $rider, 'branch.name');

        $this->assertSame('Downtown', $value);
    }

    public function test_employee_groups_include_nationality_and_department_related(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('employees');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Nationality', $labels);
        $this->assertContains('Related: Department', $labels);
        $this->assertArrayNotHasKey('nationality_id', $this->flatOptions($groups));
        $this->assertArrayNotHasKey('department_id', $this->flatOptions($groups));
    }

    public function test_rider_groups_include_nationality_related_via_country(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('riders');
        $related = collect($groups)->firstWhere('label', 'Related: Nationality');

        $this->assertIsArray($related);
        $this->assertTrue(collect($related['options'])->keys()->contains(fn ($k) => str_starts_with((string) $k, 'country.')));
        $this->assertArrayNotHasKey('nationality', $this->flatOptions($groups));
    }

    public function test_bike_groups_include_leasing_and_rental_company_related(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('bikes');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Leasing Company', $labels);
        $this->assertContains('Related: Rental Company', $labels);
        $this->assertArrayNotHasKey('company', $this->flatOptions($groups));
        $this->assertArrayNotHasKey('rental_company_id', $this->flatOptions($groups));
    }

    public function test_sim_groups_include_telecom_assignee_and_vendor_related(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('sims');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Telecom Company', $labels);
        $this->assertContains('Related: Assignee', $labels);
        $this->assertContains('Related: SIM Vendor', $labels);
    }

    public function test_fuel_card_groups_include_fuel_company_and_lost_rider_related(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('fuel_cards');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Fuel Company', $labels);
        $this->assertContains('Related: Lost Rider', $labels);
    }

    public function test_bike_rent_and_garage_customer_modules_expose_db_sources(): void
    {
        foreach (['bike_on_rent', 'garages_customers'] as $module) {
            $options = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptions($module);
            $this->assertArrayHasKey('company_name', $options);
            $this->assertArrayHasKey('name', $options);
            $this->assertArrayNotHasKey('branch_id', $options);
        }
    }

    /**
     * @param  list<array{label: string, options: array<string, string>}>  $groups
     * @return array<string, string>
     */
    private function flatOptions(array $groups): array
    {
        $flat = [];
        foreach ($groups as $group) {
            foreach ($group['options'] as $key => $label) {
                $flat[$key] = $label;
            }
        }

        return $flat;
    }
}
