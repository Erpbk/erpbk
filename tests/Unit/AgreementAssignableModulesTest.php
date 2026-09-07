<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Agreements\AgreementPlaceholderCatalog;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\TestCase;

class AgreementAssignableModulesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        View::share('currentCompany', null);
        parent::tearDown();
    }

    public function test_assignable_module_keys_use_company_catalog_filter(): void
    {
        $catalog = Mockery::mock(AgreementPlaceholderCatalog::class);
        $catalog->shouldReceive('companyAssignableModuleKeys')
            ->once()
            ->andReturn(['riders', 'customers']);

        $this->app->instance(AgreementPlaceholderCatalog::class, $catalog);

        $keys = $this->app->make(AgreementModuleService::class)->assignableModuleKeys();

        $this->assertSame(['riders', 'customers'], $keys);
    }

    public function test_company_assignable_excludes_company_disabled_modules(): void
    {
        $company = new Company();
        $company->modules_settings = ['disabled' => ['employees']];
        View::share('currentCompany', $company);

        $catalog = Mockery::mock(AgreementPlaceholderCatalog::class)->makePartial();
        $catalog->shouldReceive('adminEnabledModuleKeys')
            ->andReturn(['riders', 'employees', 'customers']);

        $this->app->instance(AgreementPlaceholderCatalog::class, $catalog);

        config(['erp_modules.modules' => [
            'riders' => 'Riders',
            'employees' => 'Employees',
            'customers' => 'Customers',
            'dashboard' => 'Dashboard',
        ]]);

        $keys = $this->app->make(AgreementPlaceholderCatalog::class)->companyAssignableModuleKeys();

        $this->assertContains('riders', $keys);
        $this->assertContains('customers', $keys);
        $this->assertNotContains('employees', $keys);
    }

    public function test_config_assignable_excludes_modules_that_never_need_agreements(): void
    {
        $keys = $this->app->make(AgreementModuleService::class)->configAssignableModuleKeys();

        foreach ([
            'cash_banks',
            'loans',
            'attendance',
            'items',
            'leads',
            'customer_invoices',
            'rta_fines',
            'rta_saliks',
            'inventory',
            'visa_expense',
            'installments',
            'license_expense',
            'legal_case',
            'expenses',
            'assets',
            'cheques',
            'passport_handover',
            'rider_inventory',
        ] as $excluded) {
            $this->assertNotContains($excluded, $keys);
        }

        $this->assertContains('riders', $keys);
        $this->assertContains('employees', $keys);
    }

    public function test_module_label_prefers_company_custom_name(): void
    {
        $company = new Company();
        $company->modules_settings = [
            'label_overrides' => [
                'riders' => 'Delivery Partners',
            ],
        ];
        View::share('currentCompany', $company);

        $label = $this->app->make(AgreementModuleService::class)->moduleLabel('riders');

        $this->assertSame('Delivery Partners', $label);
    }

    public function test_module_label_falls_back_to_default_when_no_custom_name(): void
    {
        View::share('currentCompany', null);

        $label = $this->app->make(AgreementModuleService::class)->moduleLabel('riders');

        $this->assertSame('Riders', $label);
    }

    public function test_module_label_resolves_garage_customers_menu_key_alias(): void
    {
        $company = new Company();
        $company->modules_settings = [
            'label_overrides' => [
                'garage_customers' => 'Workshop Clients',
            ],
        ];
        View::share('currentCompany', $company);

        $label = $this->app->make(AgreementModuleService::class)->moduleLabel('garages_customers');

        $this->assertSame('Workshop Clients', $label);
    }
}
