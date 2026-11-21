<?php

namespace Tests\Unit;

use App\Services\WhatsApp\Context\RiderActivityAlertContextBuilder;
use App\Services\WhatsApp\Context\WhatsAppContextBuilderRegistry;
use App\Services\WhatsApp\WhatsAppTemplateInjector;
use App\Support\WhatsApp\WhatsAppSendContext;
use Tests\TestCase;

class WhatsAppTemplateConfigTest extends TestCase
{
    public function test_whatsapp_template_config_is_internally_consistent(): void
    {
        $errors = $this->app->make(WhatsAppContextBuilderRegistry::class)->validateConfig();

        $this->assertSame([], $errors, implode("\n", $errors));
    }

    public function test_rider_activity_builder_covers_activity_template_injects(): void
    {
        $builder = $this->app->make(RiderActivityAlertContextBuilder::class);
        $provides = $builder->provides();

        foreach (['notify_absent', 'notify_multiple_absents', 'hours_not_completed', 'orders_not_completed'] as $name) {
            $injection = config("whatsapp_templates.injections.{$name}");
            $this->assertIsArray($injection, "Missing injection for {$name}");
            foreach ($injection['variables'] as $var) {
                $inject = $var['inject'];
                $this->assertContains($inject, $provides, "Missing provide for {$inject} in {$name}");
            }
        }

        $context = $builder->build([
            'rider' => [
                'name' => 'Test Rider',
                'code' => 'R-1',
                'phone' => '971501234567',
                'days_absent' => 2,
                'last_date' => '01 Jan 2026',
                'alert_date' => '2026-11-18',
                'hours' => 7.5,
                'orders' => 12,
                'target' => 10,
                'sup' => 'Supervisor',
                'company_name' => 'FleetValue',
            ],
        ]);

        $this->assertInstanceOf(WhatsAppSendContext::class, $context);
        $this->assertSame('Test Rider', $context->get('rider_name'));
        $this->assertSame('R-1', $context->get('rider_code'));
        $this->assertSame('2', $context->get('days_absent'));
        $this->assertSame('01 Jan 2026', $context->get('last_worked_date'));
        $this->assertSame('18 Nov 2026', $context->get('alert_date'));
        $this->assertSame('7.5', $context->get('hours'));
        $this->assertSame('12', $context->get('orders'));
        $this->assertSame('10', $context->get('target'));
        $this->assertSame('Supervisor', $context->get('fleet_supervisor'));
        $this->assertSame('FleetValue', $context->get('company_name'));
    }

    public function test_injector_builds_named_components_from_context(): void
    {
        $template = new \App\Models\WhatsAppTemplate([
            'name' => 'notify_absent',
            'language' => 'en',
            'param_style' => 'named',
            'status' => 'active',
            'body_preview' => 'Name: {{name}} ID: {{id}} on {{reference_date}} sup={{supervisor}} co={{company_name}}',
        ]);

        $context = WhatsAppSendContext::fromArray([
            'rider_name' => 'Ada',
            'rider_code' => 'A1',
            'alert_date' => '18 Nov 2026',
            'fleet_supervisor' => 'Sam',
            'company_name' => 'FleetValue',
        ]);

        $built = $this->app->make(WhatsAppTemplateInjector::class)->build($template, $context);
        $this->assertTrue($built['ok'] ?? false, $built['error'] ?? 'build failed');
        $this->assertSame('notify_absent', $built['template_name']);
        $params = $built['components'][0]['parameters'] ?? [];
        $byName = collect($params)->keyBy('parameter_name');
        $this->assertSame('Ada', $byName['name']['text']);
        $this->assertSame('A1', $byName['id']['text']);
        $this->assertSame('18 Nov 2026', $byName['reference_date']['text']);
        $this->assertSame('Sam', $byName['supervisor']['text']);

        $preview = $this->app->make(WhatsAppTemplateInjector::class)->previewText($template, $context);
        $this->assertStringContainsString('Ada', (string) $preview);
        $this->assertStringContainsString('A1', (string) $preview);
        $this->assertStringContainsString('18 Nov 2026', (string) $preview);
    }

    public function test_injector_builds_hours_template(): void
    {
        $template = new \App\Models\WhatsAppTemplate([
            'name' => 'hours_not_completed',
            'language' => 'en',
            'param_style' => 'named',
            'status' => 'active',
            'body_preview' => '{{name}} {{id}} {{hours}}h on {{reference_date}} {{supervisor}} {{company_name}}',
        ]);

        $context = WhatsAppSendContext::fromArray([
            'rider_name' => 'Ada',
            'rider_code' => 'A1',
            'hours' => '8',
            'alert_date' => '18 Nov 2026',
            'fleet_supervisor' => 'Sam',
            'company_name' => 'FleetValue',
        ]);

        $built = $this->app->make(WhatsAppTemplateInjector::class)->build($template, $context);
        $this->assertTrue($built['ok'] ?? false, $built['error'] ?? 'build failed');
        $byName = collect($built['components'][0]['parameters'] ?? [])->keyBy('parameter_name');
        $this->assertSame('8', $byName['hours']['text']);
        $this->assertSame('18 Nov 2026', $byName['reference_date']['text']);
    }

    public function test_builder_treats_em_dash_last_date_as_missing(): void
    {
        $context = $this->app->make(RiderActivityAlertContextBuilder::class)->build([
            'rider' => [
                'name' => 'X',
                'code' => '1',
                'last_date' => '—',
                'sup' => 'S',
                'company_name' => 'C',
                'days_absent' => 1,
            ],
        ]);

        $this->assertNull($context->get('last_worked_date'));
    }

    public function test_off_multi_day_triggers_are_removed(): void
    {
        $triggers = config('whatsapp_templates.triggers', []);
        $this->assertArrayNotHasKey('rider_activity:off2', $triggers);
        $this->assertArrayNotHasKey('rider_activity:off4', $triggers);
        $this->assertArrayNotHasKey('rider_activity:off6', $triggers);
        $this->assertArrayHasKey('rider_activity:off', $triggers);
    }

    public function test_preview_payload_lists_all_missing_injects(): void
    {
        $template = new \App\Models\WhatsAppTemplate([
            'name' => 'notify_absent',
            'language' => 'en',
            'param_style' => 'named',
            'status' => 'active',
            'body_preview' => 'Name: {{name}}',
        ]);

        $context = WhatsAppSendContext::fromArray([
            'rider_name' => 'Ada',
            'rider_code' => 'A1',
            // missing alert_date, fleet_supervisor, company_name
        ]);

        $payload = $this->app->make(WhatsAppTemplateInjector::class)->previewPayload($template, $context);
        $this->assertFalse($payload['ok']);
        $this->assertNull($payload['text']);
        $this->assertEqualsCanonicalizing(
            ['alert_date', 'fleet_supervisor', 'company_name'],
            $payload['missing_injects']
        );
        $this->assertNotEmpty($payload['missing_inject_labels']);
        $this->assertCount(count($payload['missing_injects']), $payload['missing_inject_labels']);
    }
}
