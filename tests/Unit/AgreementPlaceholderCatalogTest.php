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
        $this->assertArrayHasKey('company_contact', $options);
        $this->assertArrayHasKey('company_address', $options);
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

    public function test_source_groups_include_related_other_for_all_modules(): void
    {
        $catalog = $this->app->make(AgreementPlaceholderCatalog::class);

        foreach (['riders', 'employees', 'bikes', 'sims', 'fuel_cards'] as $module) {
            $groups = $catalog->sourceFieldOptionGroups($module);
            $this->assertContains('Related: Other', array_column($groups, 'label'), "Missing Related: Other for {$module}");
        }

        $systemLabels = array_column($catalog->sourceFieldOptionGroups('system'), 'label');
        $this->assertNotContains('Related: Other', $systemLabels);
    }

    public function test_rider_related_other_includes_vehicle_and_fuel_card_options(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('riders');
        $other = collect($groups)->firstWhere('label', 'Related: Other');

        $this->assertIsArray($other);
        $this->assertSame([
            'other.vehicle_plate',
            'other.vehicle_chassis',
            'other.vehicle_engine',
            'other.vehicle_color',
            'other.fuelCard_no',
            'other.fuelCard_monthly_charges',
            'other.fuelCard_monthly_limit',
        ], array_keys($other['options']));
    }

    public function test_employee_related_other_is_empty_until_configured(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('employees');
        $other = collect($groups)->firstWhere('label', 'Related: Other');

        $this->assertIsArray($other);
        $this->assertSame([], $other['options']);
    }

    public function test_system_source_options_exclude_module_db_fields(): void
    {
        $options = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptions('system');

        $this->assertSame([
            'company_name' => 'Company name',
            'company_contact' => 'Company contact',
            'company_address' => 'Company address',
            'current_date' => 'Current date',
        ], $options);
    }

    public function test_general_source_options_are_system_only(): void
    {
        $options = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptions('general');

        $this->assertSame([
            'company_name' => 'Company name',
            'company_contact' => 'Company contact',
            'company_address' => 'Company address',
            'current_date' => 'Current date',
        ], $options);
    }

    public function test_general_placeholders_are_system_only(): void
    {
        $grouped = $this->app->make(AgreementPlaceholderCatalog::class)->groupedForModule('general');
        $tokens = collect($grouped)->flatten()->pluck('placeholder')->filter()->all();

        $this->assertContains('{current_date}', $tokens);
        $this->assertContains('{company_name}', $tokens);
        $this->assertNotContains('{rider_name}', $tokens);
        $this->assertNotContains('{plate_number}', $tokens);
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

    public function test_code_placeholders_are_wrapped_as_left_to_right_values(): void
    {
        $resolver = new AgreementPlaceholderResolver();

        $html = $resolver->replace(
            '<p dir="rtl">رقم اللوحة : {plate_number}<br>{rider_name}<br><span dir="ltr" class="field-value">{chassis_number}</span></p>',
            [
                '{plate_number}' => '2/30178',
                '{chassis_number}' => 'MD2A11CX3RCG00469',
                '{rider_name}' => 'رضوان',
            ]
        );

        $this->assertSame(2, substr_count($html, 'class="field-value"'));
        $this->assertStringContainsString('<span dir="ltr" class="field-value">2/30178</span>', $html);
        $this->assertStringContainsString('<span dir="ltr" class="field-value">MD2A11CX3RCG00469</span>', $html);
        $this->assertStringContainsString('رضوان', $html);
        $this->assertStringNotContainsString('field-value">رضوان', $html);
        $this->assertStringNotContainsString('{plate_number}', $html);
        $this->assertStringNotContainsString('{chassis_number}', $html);
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

    public function test_resolver_reads_rider_related_other_vehicle_and_fuel_sources(): void
    {
        $bike = new \App\Models\Bikes([
            'bike_code' => 'BK01',
            'plate' => 'A12345',
            'emirates' => 'Dubai',
            'chassis_number' => 'CHS-1',
            'engine' => 'ENG-9',
            'color' => 'Red',
        ]);

        $card = new \App\Models\FuelCards([
            'card_number' => '1234567890123456',
            'service_charges' => '25.50',
            'monthly_limit' => '500.00',
        ]);

        $rider = new Riders();
        $rider->id = 42;
        $rider->setRelation('bikes', $bike);

        $resolver = new class ($card) extends AgreementPlaceholderResolver {
            public function __construct(private \App\Models\FuelCards $stubCard)
            {
            }

            protected function assignedFuelCardForRider(\Illuminate\Database\Eloquent\Model $record): ?\App\Models\FuelCards
            {
                return $this->stubCard;
            }
        };

        $method = new \ReflectionMethod($resolver, 'resolveSourceKey');
        $method->setAccessible(true);

        $this->assertSame('BK01-A12345 (Dubai)', $method->invoke($resolver, 'riders', $rider, 'other.vehicle_plate'));
        $this->assertSame('CHS-1', $method->invoke($resolver, 'riders', $rider, 'other.vehicle_chassis'));
        $this->assertSame('ENG-9', $method->invoke($resolver, 'riders', $rider, 'other.vehicle_engine'));
        $this->assertSame('Red', $method->invoke($resolver, 'riders', $rider, 'other.vehicle_color'));
        $this->assertSame('1234567890123456', $method->invoke($resolver, 'riders', $rider, 'other.fuelCard_no'));
        $this->assertSame('25.50', $method->invoke($resolver, 'riders', $rider, 'other.fuelCard_monthly_charges'));
        $this->assertSame('500.00', $method->invoke($resolver, 'riders', $rider, 'other.fuelCard_monthly_limit'));
    }

    public function test_resolver_reads_bike_assigned_to_from_rider_or_rental_company(): void
    {
        $resolver = $this->app->make(AgreementPlaceholderResolver::class);
        $method = new \ReflectionMethod($resolver, 'resolveSourceKey');
        $method->setAccessible(true);

        $rider = new Riders([
            'name' => 'Ahmed',
            'rider_id' => 'R-100',
            'email' => 'ahmed@example.com',
            'emirate_id' => '784-1',
            'personal_contact' => '0501111111',
        ]);

        $bikeWithRider = new \App\Models\Bikes();
        $bikeWithRider->setRelation('rider', $rider);
        $bikeWithRider->setRelation('rentalCompany', null);

        $this->assertSame('Ahmed', $method->invoke($resolver, 'bikes', $bikeWithRider, 'assignedTo.name'));
        $this->assertSame('R-100', $method->invoke($resolver, 'bikes', $bikeWithRider, 'assignedTo.id'));
        $this->assertSame('ahmed@example.com', $method->invoke($resolver, 'bikes', $bikeWithRider, 'assignedTo.email'));
        $this->assertSame('', $method->invoke($resolver, 'bikes', $bikeWithRider, 'assignedTo.address'));
        $this->assertSame('784-1', $method->invoke($resolver, 'bikes', $bikeWithRider, 'assignedTo.emirates_id'));
        $this->assertSame('0501111111', $method->invoke($resolver, 'bikes', $bikeWithRider, 'assignedTo.contact_no'));

        $company = new \App\Models\BikeRentCompany([
            'name' => 'Rent Co',
            'email' => 'rent@example.com',
            'address' => 'Dubai Marina',
            'emirates_id' => '784-9',
            'company_contact' => '0502222222',
        ]);
        $company->id = 77;

        $bikeWithCompany = new \App\Models\Bikes();
        $bikeWithCompany->setRelation('rider', null);
        $bikeWithCompany->setRelation('rentalCompany', $company);

        $this->assertSame('Rent Co', $method->invoke($resolver, 'bikes', $bikeWithCompany, 'assignedTo.name'));
        $this->assertSame('77', $method->invoke($resolver, 'bikes', $bikeWithCompany, 'assignedTo.id'));
        $this->assertSame('rent@example.com', $method->invoke($resolver, 'bikes', $bikeWithCompany, 'assignedTo.email'));
        $this->assertSame('Dubai Marina', $method->invoke($resolver, 'bikes', $bikeWithCompany, 'assignedTo.address'));
        $this->assertSame('784-9', $method->invoke($resolver, 'bikes', $bikeWithCompany, 'assignedTo.emirates_id'));
        $this->assertSame('0502222222', $method->invoke($resolver, 'bikes', $bikeWithCompany, 'assignedTo.contact_no'));
    }

    public function test_bike_on_rent_related_other_includes_assigned_vehicle_list(): void
    {
        $catalog = $this->app->make(AgreementPlaceholderCatalog::class);

        foreach (['bike_on_rent', 'garages_customers', 'leasing_companies'] as $module) {
            $other = collect($catalog->sourceFieldOptionGroups($module))->firstWhere('label', 'Related: Other');
            $this->assertIsArray($other, "Missing Related: Other for {$module}");
            $this->assertArrayHasKey('other.assigned_vehicle_list', $other['options'], $module);
            $this->assertSame('Assigned vehicle list', $other['options']['other.assigned_vehicle_list'], $module);
        }
    }

    public function test_bike_code_emirates_plate_concat_format(): void
    {
        $resolver = $this->app->make(AgreementPlaceholderResolver::class);
        $method = new \ReflectionMethod($resolver, 'formatBikeCodeEmiratesPlateConcat');
        $method->setAccessible(true);

        $bike = new \App\Models\Bikes([
            'bike_code' => '2',
            'emirates' => 'DXB',
            'plate' => '21652',
        ]);

        $this->assertSame('2DXB21652', $method->invoke($resolver, $bike));
    }

    public function test_resolver_formats_assigned_vehicle_list_for_rent_garage_and_leasing(): void
    {
        $bikes = collect([
            new \App\Models\Bikes(['bike_code' => '2', 'emirates' => 'DXB', 'plate' => '21652']),
            new \App\Models\Bikes(['bike_code' => '3', 'emirates' => 'AUH', 'plate' => '100']),
        ]);

        $resolver = new class ($bikes) extends AgreementPlaceholderResolver {
            public function __construct(private \Illuminate\Support\Collection $stubBikes)
            {
            }

            protected function bikesAssignedWhere(\Illuminate\Database\Eloquent\Model $record, string $bikeFkColumn): \Illuminate\Support\Collection
            {
                return $this->stubBikes;
            }
        };

        $method = new \ReflectionMethod($resolver, 'resolveSourceKey');
        $method->setAccessible(true);

        $rentCompany = new \App\Models\BikeRentCompany(['name' => 'Rent Co']);
        $rentCompany->id = 15;
        $this->assertSame(
            '2DXB21652 3AUH100',
            $method->invoke($resolver, 'bike_on_rent', $rentCompany, 'other.assigned_vehicle_list')
        );
        $this->assertSame(
            '2DXB21652 3AUH100',
            $method->invoke($resolver, 'garages_customers', $rentCompany, 'other.assigned_vehicle_list')
        );

        $leasing = new \App\Models\LeasingCompanies(['name' => 'Lease Co']);
        $leasing->id = 22;
        $this->assertSame(
            '2DXB21652 3AUH100',
            $method->invoke($resolver, 'leasing_companies', $leasing, 'other.assigned_vehicle_list')
        );
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

    public function test_bike_groups_include_assigned_to_instead_of_rider_and_rental(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('bikes');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Leasing Company', $labels);
        $this->assertContains('Assigned To:', $labels);
        $this->assertNotContains('Related: Rider', $labels);
        $this->assertNotContains('Related: Rental Company', $labels);
        $this->assertArrayNotHasKey('company', $this->flatOptions($groups));
        $this->assertArrayNotHasKey('rider_id', $this->flatOptions($groups));
        $this->assertArrayNotHasKey('rental_company_id', $this->flatOptions($groups));

        $assigned = collect($groups)->firstWhere('label', 'Assigned To:');
        $this->assertIsArray($assigned);
        $this->assertSame([
            'assignedTo.name',
            'assignedTo.id',
            'assignedTo.email',
            'assignedTo.address',
            'assignedTo.emirates_id',
            'assignedTo.contact_no',
        ], array_keys($assigned['options']));
    }

    public function test_sim_groups_include_fixed_assignee_telecom_and_vendor_related(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('sims');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Telecom Company', $labels);
        $this->assertContains('Related: Assignee', $labels);
        $this->assertContains('Related: SIM Vendor', $labels);
        $this->assertArrayNotHasKey('assign_to', $this->flatOptions($groups));

        $assignee = collect($groups)->firstWhere('label', 'Related: Assignee');
        $this->assertIsArray($assignee);
        $this->assertSame([
            'assignee.id',
            'assignee.name',
            'assignee.emirates_id',
            'assignee.contact_no',
            'assignee.address',
            'assignee.designation',
            'assignee.joining_date',
        ], array_keys($assignee['options']));
    }

    public function test_resolver_reads_sim_assignee_from_rider_or_employee(): void
    {
        $resolver = $this->app->make(AgreementPlaceholderResolver::class);
        $method = new \ReflectionMethod($resolver, 'resolveSourceKey');
        $method->setAccessible(true);

        $rider = new Riders([
            'name' => 'Sara',
            'rider_id' => 'R-55',
            'emirate_id' => '784-5',
            'personal_contact' => '0503333333',
            'designation' => 'Rider',
            'doj' => '2024-02-01',
        ]);

        $simWithRider = new \App\Models\Sims(['assign_type' => 'rider', 'assign_to' => 1]);
        $simWithRider->setRelation('riders', $rider);
        $simWithRider->setRelation('employee', null);

        $this->assertSame('R-55', $method->invoke($resolver, 'sims', $simWithRider, 'assignee.id'));
        $this->assertSame('Sara', $method->invoke($resolver, 'sims', $simWithRider, 'assignee.name'));
        $this->assertSame('784-5', $method->invoke($resolver, 'sims', $simWithRider, 'assignee.emirates_id'));
        $this->assertSame('0503333333', $method->invoke($resolver, 'sims', $simWithRider, 'assignee.contact_no'));
        $this->assertSame('', $method->invoke($resolver, 'sims', $simWithRider, 'assignee.address'));
        $this->assertSame('Rider', $method->invoke($resolver, 'sims', $simWithRider, 'assignee.designation'));
        $this->assertSame('01-Feb-2024', $method->invoke($resolver, 'sims', $simWithRider, 'assignee.joining_date'));

        $employee = new \App\Models\Employee([
            'name' => 'Omar',
            'employee_id' => 'E-9',
            'emirate_id' => '784-8',
            'company_contact' => '0504444444',
            'address' => 'Sharjah',
            'designation' => 'Supervisor',
            'doj' => '2023-06-15',
        ]);

        $simWithEmployee = new \App\Models\Sims(['assign_type' => 'employee', 'assign_to' => 2]);
        $simWithEmployee->setRelation('employee', $employee);
        $simWithEmployee->setRelation('riders', null);

        $this->assertSame('E-9', $method->invoke($resolver, 'sims', $simWithEmployee, 'assignee.id'));
        $this->assertSame('Omar', $method->invoke($resolver, 'sims', $simWithEmployee, 'assignee.name'));
        $this->assertSame('784-8', $method->invoke($resolver, 'sims', $simWithEmployee, 'assignee.emirates_id'));
        $this->assertSame('0504444444', $method->invoke($resolver, 'sims', $simWithEmployee, 'assignee.contact_no'));
        $this->assertSame('Sharjah', $method->invoke($resolver, 'sims', $simWithEmployee, 'assignee.address'));
        $this->assertSame('Supervisor', $method->invoke($resolver, 'sims', $simWithEmployee, 'assignee.designation'));
        $this->assertSame('15-Jun-2023', $method->invoke($resolver, 'sims', $simWithEmployee, 'assignee.joining_date'));
    }

    public function test_fuel_card_groups_include_fuel_company_and_lost_by(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('fuel_cards');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Fuel Company', $labels);
        $this->assertContains('Related: Lost By', $labels);
        $this->assertContains('Related: Assigned To', $labels);
        $this->assertNotContains('Related: Lost Rider', $labels);
        $this->assertArrayNotHasKey('lost_rider_id', $this->flatOptions($groups));
        $this->assertArrayNotHasKey('assigned_to', $this->flatOptions($groups));

        $lostBy = collect($groups)->firstWhere('label', 'Related: Lost By');
        $this->assertIsArray($lostBy);
        $this->assertSame([
            'lostBy.id',
            'lostBy.name',
            'lostBy.emirates_id',
            'lostBy.contact_no',
            'lostBy.address',
            'lostBy.designation',
            'lostBy.joining_date',
        ], array_keys($lostBy['options']));

        $assignedTo = collect($groups)->firstWhere('label', 'Related: Assigned To');
        $this->assertIsArray($assignedTo);
        $this->assertSame([
            'assignedTo.id',
            'assignedTo.name',
            'assignedTo.emirates_id',
            'assignedTo.contact_no',
            'assignedTo.address',
            'assignedTo.designation',
            'assignedTo.joining_date',
        ], array_keys($assignedTo['options']));
    }

    public function test_resolver_reads_fuel_card_assigned_to_from_rider(): void
    {
        $resolver = $this->app->make(AgreementPlaceholderResolver::class);
        $method = new \ReflectionMethod($resolver, 'resolveSourceKey');
        $method->setAccessible(true);

        $rider = new Riders([
            'name' => 'Nour',
            'rider_id' => 'R-88',
            'emirate_id' => '784-8',
            'personal_contact' => '0506666666',
            'designation' => 'Rider',
            'doj' => '2021-08-20',
        ]);

        $card = new \App\Models\FuelCards();
        $card->setRelation('rider', $rider);

        $this->assertSame('R-88', $method->invoke($resolver, 'fuel_cards', $card, 'assignedTo.id'));
        $this->assertSame('Nour', $method->invoke($resolver, 'fuel_cards', $card, 'assignedTo.name'));
        $this->assertSame('784-8', $method->invoke($resolver, 'fuel_cards', $card, 'assignedTo.emirates_id'));
        $this->assertSame('0506666666', $method->invoke($resolver, 'fuel_cards', $card, 'assignedTo.contact_no'));
        $this->assertSame('', $method->invoke($resolver, 'fuel_cards', $card, 'assignedTo.address'));
        $this->assertSame('Rider', $method->invoke($resolver, 'fuel_cards', $card, 'assignedTo.designation'));
        $this->assertSame('20-Aug-2021', $method->invoke($resolver, 'fuel_cards', $card, 'assignedTo.joining_date'));
    }

    public function test_resolver_reads_fuel_card_lost_by_from_rider(): void
    {
        $resolver = $this->app->make(AgreementPlaceholderResolver::class);
        $method = new \ReflectionMethod($resolver, 'resolveSourceKey');
        $method->setAccessible(true);

        $rider = new Riders([
            'name' => 'Hassan',
            'rider_id' => 'R-70',
            'emirate_id' => '784-7',
            'personal_contact' => '0505555555',
            'designation' => 'Courier',
            'doj' => '2022-03-10',
        ]);

        $card = new \App\Models\FuelCards();
        $card->setRelation('lostRider', $rider);

        $this->assertSame('R-70', $method->invoke($resolver, 'fuel_cards', $card, 'lostBy.id'));
        $this->assertSame('Hassan', $method->invoke($resolver, 'fuel_cards', $card, 'lostBy.name'));
        $this->assertSame('784-7', $method->invoke($resolver, 'fuel_cards', $card, 'lostBy.emirates_id'));
        $this->assertSame('0505555555', $method->invoke($resolver, 'fuel_cards', $card, 'lostBy.contact_no'));
        $this->assertSame('', $method->invoke($resolver, 'fuel_cards', $card, 'lostBy.address'));
        $this->assertSame('Courier', $method->invoke($resolver, 'fuel_cards', $card, 'lostBy.designation'));
        $this->assertSame('10-Mar-2022', $method->invoke($resolver, 'fuel_cards', $card, 'lostBy.joining_date'));
    }

    public function test_sim_groups_include_lost_by_instead_of_lost_rider(): void
    {
        $groups = $this->app->make(AgreementPlaceholderCatalog::class)->sourceFieldOptionGroups('sims');
        $labels = array_column($groups, 'label');

        $this->assertContains('Related: Lost By', $labels);
        $this->assertNotContains('Related: Lost Rider', $labels);
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
