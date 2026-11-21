<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppTemplate;
use App\Support\WhatsApp\WhatsAppSendContext;
use Illuminate\Support\Facades\Log;

class WhatsAppTemplateInjector
{
    /**
     * Build Meta template components from config injections + typed context.
     * Fail-closed: returns ok=false when any required inject is missing.
     *
     * @return array{
     *   ok: bool,
     *   components?: list<array<string, mixed>>,
     *   error?: string,
     *   missing_injects?: list<string>,
     *   template_name?: string,
     *   language?: string
     * }
     */
    public function build(WhatsAppTemplate $template, WhatsAppSendContext $context): array
    {
        $name = trim((string) $template->name);
        $language = trim((string) ($template->language ?: 'en')) ?: 'en';
        $injection = $this->injectionFor($name, $language);

        if ($injection === null) {
            return [
                'ok' => false,
                'error' => "No injection config for template [{$name}/{$language}].",
                'missing_injects' => [],
                'template_name' => $name,
                'language' => $language,
            ];
        }

        $variables = is_array($injection['variables'] ?? null) ? $injection['variables'] : [];
        if ($variables === []) {
            return [
                'ok' => false,
                'error' => "Injection config for [{$name}] has no variables.",
                'missing_injects' => [],
                'template_name' => $name,
                'language' => $language,
            ];
        }

        $paramStyle = (string) ($injection['param_style'] ?? $template->param_style ?? 'positional');
        $allowedInjectKeys = array_keys(config('whatsapp_templates.inject_keys', []));

        $byComponent = [];
        $missingInjects = [];
        foreach ($variables as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $injectKey = trim((string) ($row['inject'] ?? ''));
            if ($injectKey === '' || ! in_array($injectKey, $allowedInjectKeys, true)) {
                return [
                    'ok' => false,
                    'error' => "Invalid inject key [{$injectKey}] at variable index {$index}.",
                    'missing_injects' => [],
                    'template_name' => $name,
                    'language' => $language,
                ];
            }

            $value = $context->get($injectKey);
            if ($value === null) {
                $missingInjects[] = $injectKey;
                continue;
            }

            $component = strtolower(trim((string) ($row['component'] ?? 'body'))) ?: 'body';
            if (! in_array($component, ['body', 'header', 'button'], true)) {
                $component = 'body';
            }

            $param = [
                'type' => 'text',
                'text' => mb_substr($value, 0, 1024),
            ];
            if ($paramStyle === 'named') {
                $parameterName = trim((string) ($row['parameter_name'] ?? ''));
                if ($parameterName === '') {
                    return [
                        'ok' => false,
                        'error' => "Named template [{$name}] missing parameter_name at index {$index}.",
                        'missing_injects' => [],
                        'template_name' => $name,
                        'language' => $language,
                    ];
                }
                $param['parameter_name'] = $parameterName;
            }

            $byComponent[$component][] = $param;
        }

        if ($missingInjects !== []) {
            $missingInjects = array_values(array_unique($missingInjects));
            Log::info('whatsapp.inject_missing', [
                'template' => $name,
                'injects' => $missingInjects,
            ]);

            return [
                'ok' => false,
                'error' => 'Missing required inject value(s): '.implode(', ', $missingInjects).'.',
                'missing_injects' => $missingInjects,
                'template_name' => $name,
                'language' => $language,
            ];
        }

        $components = [];
        foreach (['header', 'body', 'button'] as $component) {
            if (empty($byComponent[$component])) {
                continue;
            }
            $entry = [
                'type' => $component,
                'parameters' => $byComponent[$component],
            ];
            if ($component === 'button') {
                $entry['sub_type'] = 'quick_reply';
                $entry['index'] = '0';
            }
            $components[] = $entry;
        }

        return [
            'ok' => true,
            'components' => $components,
            'missing_injects' => [],
            'template_name' => $name,
            'language' => $language,
        ];
    }

    /**
     * Preview body text by substituting inject values into body_preview or parameter list.
     */
    public function previewText(WhatsAppTemplate $template, WhatsAppSendContext $context): ?string
    {
        $payload = $this->previewPayload($template, $context);

        return $payload['text'] ?? null;
    }

    /**
     * @return array{
     *   ok: bool,
     *   text: ?string,
     *   missing_injects: list<string>,
     *   missing_inject_labels: list<string>,
     *   error: ?string
     * }
     */
    public function previewPayload(WhatsAppTemplate $template, WhatsAppSendContext $context): array
    {
        $built = $this->build($template, $context);
        $missing = is_array($built['missing_injects'] ?? null) ? $built['missing_injects'] : [];
        $labelsMap = config('whatsapp_templates.inject_keys', []);
        $labels = [];
        foreach ($missing as $key) {
            $labels[] = is_string($labelsMap[$key] ?? null) && $labelsMap[$key] !== ''
                ? (string) $labelsMap[$key]
                : (string) $key;
        }

        if (! ($built['ok'] ?? false)) {
            return [
                'ok' => false,
                'text' => null,
                'missing_injects' => $missing,
                'missing_inject_labels' => $labels,
                'error' => (string) ($built['error'] ?? 'inject_failed'),
            ];
        }

        $preview = trim((string) ($template->body_preview ?? ''));
        if ($preview !== '') {
            $injection = $this->injectionFor($template->name, $template->language ?: 'en') ?? [];
            foreach (($injection['variables'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $param = trim((string) ($row['parameter_name'] ?? ''));
                $inject = trim((string) ($row['inject'] ?? ''));
                $value = $context->get($inject) ?? '';
                if ($param !== '') {
                    $preview = str_replace('{{'.$param.'}}', $value, $preview);
                }
            }

            return [
                'ok' => true,
                'text' => $preview,
                'missing_injects' => [],
                'missing_inject_labels' => [],
                'error' => null,
            ];
        }

        $parts = [];
        foreach ($built['components'] as $component) {
            foreach ($component['parameters'] ?? [] as $param) {
                $parts[] = (string) ($param['text'] ?? '');
            }
        }

        return [
            'ok' => true,
            'text' => implode(' · ', array_filter($parts)),
            'missing_injects' => [],
            'missing_inject_labels' => [],
            'error' => null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function injectionFor(string $templateName, string $language = 'en'): ?array
    {
        $all = config('whatsapp_templates.injections', []);
        if (! is_array($all) || ! isset($all[$templateName]) || ! is_array($all[$templateName])) {
            return null;
        }

        $row = $all[$templateName];
        $configuredLanguage = trim((string) ($row['language'] ?? 'en')) ?: 'en';
        $language = trim($language) ?: 'en';
        if (strcasecmp($configuredLanguage, $language) !== 0) {
            return null;
        }

        return $row;
    }

    /**
     * @return list<string>
     */
    public function triggerKeysForTemplateName(string $templateName): array
    {
        $injection = config('whatsapp_templates.injections.'.$templateName);
        if (! is_array($injection)) {
            return [];
        }
        $keys = $injection['trigger_keys'] ?? [];

        return is_array($keys) ? array_values(array_map('strval', $keys)) : [];
    }
}
