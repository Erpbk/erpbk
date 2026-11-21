<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConnection;
use App\Models\WhatsAppTemplate;
use App\Models\WhatsAppTemplateBinding;

class WhatsAppTemplateBindingService
{
    /**
     * @return array{ready: bool, message?: string, binding?: WhatsAppTemplateBinding|null, template?: WhatsAppTemplate|null, connection_id?: int|null}
     */
    public function resolve(string $triggerKey): array
    {
        $triggerKey = trim($triggerKey);
        if ($triggerKey === '') {
            return ['ready' => false, 'message' => 'Trigger key is required.', 'binding' => null, 'template' => null];
        }

        $binding = WhatsAppTemplateBinding::query()
            ->with(['template'])
            ->where('trigger_key', $triggerKey)
            ->first();

        if (! $binding) {
            return [
                'ready' => false,
                'message' => "No WhatsApp template bound for [{$triggerKey}].",
                'binding' => null,
                'template' => null,
            ];
        }

        $template = $binding->template;
        if (! $template instanceof WhatsAppTemplate || ! $template->isActive()) {
            return [
                'ready' => false,
                'message' => "Bound WhatsApp template for [{$triggerKey}] is missing or inactive.",
                'binding' => $binding,
                'template' => null,
            ];
        }

        // Injection config must exist for this Meta name.
        $injection = app(WhatsAppTemplateInjector::class)
            ->injectionFor($template->name, $template->language ?: 'en');
        if ($injection === null) {
            return [
                'ready' => false,
                'message' => "No injection config for bound template [{$template->name}].",
                'binding' => $binding,
                'template' => $template,
            ];
        }

        $connectionId = $binding->whatsapp_connection_id
            ? (int) $binding->whatsapp_connection_id
            : null;

        return [
            'ready' => true,
            'binding' => $binding,
            'template' => $template,
            'connection_id' => $connectionId,
        ];
    }

    /**
     * @return array<string, array{trigger_key: string, label: string, template_id: int|null, template_label: string|null}>
     */
    public function listingForAdmin(): array
    {
        $triggers = config('whatsapp_templates.triggers', []);
        $bindings = WhatsAppTemplateBinding::query()->with('template')->get()->keyBy('trigger_key');
        $out = [];
        foreach ($triggers as $key => $label) {
            $binding = $bindings->get($key);
            $out[$key] = [
                'trigger_key' => $key,
                'label' => (string) $label,
                'template_id' => $binding?->whatsapp_template_id ? (int) $binding->whatsapp_template_id : null,
                'connection_id' => $binding?->whatsapp_connection_id ? (int) $binding->whatsapp_connection_id : null,
                'template_label' => $binding?->template?->displayLabel(),
            ];
        }

        return $out;
    }

    public function upsert(string $triggerKey, ?int $templateId, ?int $connectionId = null): void
    {
        $triggerKey = trim($triggerKey);
        if ($triggerKey === '') {
            return;
        }

        if (! $templateId) {
            WhatsAppTemplateBinding::query()->where('trigger_key', $triggerKey)->delete();

            return;
        }

        WhatsAppTemplateBinding::query()->updateOrCreate(
            ['trigger_key' => $triggerKey],
            [
                'whatsapp_template_id' => $templateId,
                'whatsapp_connection_id' => $connectionId ?: null,
            ]
        );
    }
}
