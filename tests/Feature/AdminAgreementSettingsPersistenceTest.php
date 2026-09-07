<?php

namespace Tests\Feature;

use App\Models\AdminAgreementAssignableModule;
use App\Models\AdminAgreementPlaceholder;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAgreementSettingsPersistenceTest extends TestCase
{
    public function test_assignable_module_row_can_be_toggled_on_admin_connection(): void
    {
        $key = 'riders';
        $row = AdminAgreementAssignableModule::query()->firstOrCreate(
            ['module_key' => $key],
            ['enabled' => true, 'sort_order' => 1]
        );

        $original = (bool) $row->enabled;
        $row->enabled = ! $original;
        $row->save();

        $fresh = AdminAgreementAssignableModule::query()->where('module_key', $key)->first();
        $this->assertNotNull($fresh);
        $this->assertSame(! $original, (bool) $fresh->enabled);

        $fresh->enabled = $original;
        $fresh->save();
    }

    public function test_placeholder_can_be_persisted_on_admin_connection(): void
    {
        $token = '{test_'.Str::lower(Str::random(8)).'}';

        $placeholder = AdminAgreementPlaceholder::query()->create([
            'module_key' => 'system',
            'placeholder' => $token,
            'description' => 'Test token',
            'group_label' => 'Tests',
            'source_key' => 'current_date',
            'sort_order' => 9999,
        ]);

        $this->assertDatabaseHas('admin_agreement_placeholders', [
            'id' => $placeholder->id,
            'placeholder' => $token,
            'module_key' => 'system',
        ], 'mysql_admin');

        $placeholder->delete();
    }

    public function test_placeholder_token_without_braces_is_normalized_on_model_shape(): void
    {
        $controller = new \App\Http\Controllers\Admin\AdminAgreementSettingsController();
        $method = new \ReflectionMethod($controller, 'normalizePlaceholderToken');
        $method->setAccessible(true);

        $this->assertSame('{rider_name}', $method->invoke($controller, 'rider_name'));
        $this->assertSame('{rider_name}', $method->invoke($controller, '{rider_name}'));
        $this->assertSame('{rider_name}', $method->invoke($controller, ' {rider_name} '));
        $this->assertSame('', $method->invoke($controller, '   '));
    }
}
