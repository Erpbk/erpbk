<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class AgreementSettingsModuleAssignmentTest extends TestCase
{
    public function test_validation_accepts_single_module_string(): void
    {
        $keys = ['riders', 'employees'];
        $validator = Validator::make(
            ['assigned_modules' => 'riders'],
            ['assigned_modules' => ['required', 'string', Rule::in($keys)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_validation_rejects_multiple_modules_array(): void
    {
        $keys = ['riders', 'employees'];
        $validator = Validator::make(
            ['assigned_modules' => ['riders', 'employees']],
            ['assigned_modules' => ['required', 'string', Rule::in($keys)]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('assigned_modules', $validator->errors()->toArray());
    }

    public function test_agreements_index_view_is_unified_without_contract_template(): void
    {
        $blade = file_get_contents(resource_path('views/settings/agreements/index.blade.php'));

        $this->assertStringNotContainsString('Contract Template', $blade);
        $this->assertStringNotContainsString('nav-tabs', $blade);
        $this->assertStringContainsString('filter-module', $blade);
        $this->assertStringContainsString('name="name"', $blade);
        $this->assertStringContainsString('name="status"', $blade);
        $this->assertStringContainsString('>Module</th>', $blade);
        $this->assertStringContainsString('agreements.preview', $blade);
        $this->assertStringContainsString('agreements.templates', $blade);
        $this->assertStringContainsString('ti-dots', $blade);
        $this->assertStringContainsString('dropdown-menu', $blade);
    }
}
