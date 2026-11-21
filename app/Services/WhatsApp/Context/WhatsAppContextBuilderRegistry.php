<?php

namespace App\Services\WhatsApp\Context;

use App\Support\WhatsApp\WhatsAppSendContext;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;

class WhatsAppContextBuilderRegistry
{
    public function __construct(private readonly Container $container)
    {
    }

    public function forTrigger(string $triggerKey): ?WhatsAppContextBuilder
    {
        $triggerKey = trim($triggerKey);
        if ($triggerKey === '') {
            return null;
        }

        $map = config('whatsapp_templates.builders', []);
        if (! is_array($map)) {
            return null;
        }

        $class = $map[$triggerKey] ?? null;
        if (! is_string($class) || $class === '') {
            $class = $this->wildcardClass($triggerKey, $map);
        }

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return null;
        }

        $builder = $this->container->make($class);

        return $builder instanceof WhatsAppContextBuilder ? $builder : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function build(string $triggerKey, array $payload): ?WhatsAppSendContext
    {
        $builder = $this->forTrigger($triggerKey);

        return $builder?->build($payload);
    }

    /**
     * Static config integrity checks (for tests / boot).
     *
     * @return list<string>
     */
    public function validateConfig(): array
    {
        $errors = [];
        $injectKeys = array_keys(config('whatsapp_templates.inject_keys', []));
        $contextProps = $this->contextPropertyNames();

        foreach ($injectKeys as $key) {
            if (! in_array($key, $contextProps, true)) {
                $errors[] = "inject_keys [{$key}] is not a WhatsAppSendContext property.";
            }
        }

        foreach (config('whatsapp_templates.injections', []) as $templateName => $row) {
            if (! is_array($row)) {
                $errors[] = "injections.{$templateName} must be an array.";
                continue;
            }
            foreach (($row['variables'] ?? []) as $i => $var) {
                if (! is_array($var)) {
                    continue;
                }
                $inject = trim((string) ($var['inject'] ?? ''));
                if ($inject === '' || ! in_array($inject, $injectKeys, true)) {
                    $errors[] = "injections.{$templateName}.variables[{$i}] inject [{$inject}] is not in inject_keys.";
                }
            }
            foreach (($row['trigger_keys'] ?? []) as $trigger) {
                $trigger = trim((string) $trigger);
                if ($trigger === '') {
                    continue;
                }
                if ($this->forTrigger($trigger) === null) {
                    $errors[] = "No context builder registered for trigger [{$trigger}] (template {$templateName}).";
                } else {
                    $builder = $this->forTrigger($trigger);
                    $provides = $builder?->provides() ?? [];
                    foreach (($row['variables'] ?? []) as $var) {
                        if (! is_array($var)) {
                            continue;
                        }
                        $inject = trim((string) ($var['inject'] ?? ''));
                        if ($inject !== '' && ! in_array($inject, $provides, true)) {
                            $errors[] = "Builder for [{$trigger}] does not provide inject [{$inject}] required by {$templateName}.";
                        }
                    }
                }
            }
        }

        foreach (config('whatsapp_templates.builders', []) as $key => $class) {
            if (! is_string($class) || ! class_exists($class)) {
                $errors[] = "builders.{$key} class [{$class}] does not exist.";
                continue;
            }
            if (! is_subclass_of($class, WhatsAppContextBuilder::class)) {
                $errors[] = "builders.{$key} class [{$class}] must implement WhatsAppContextBuilder.";
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function wildcardClass(string $triggerKey, array $map): ?string
    {
        if (str_starts_with($triggerKey, 'rider_activity:') && isset($map['rider_activity:*'])) {
            return is_string($map['rider_activity:*']) ? $map['rider_activity:*'] : null;
        }
        if (str_starts_with($triggerKey, 'notification:') && isset($map['notification:*'])) {
            return is_string($map['notification:*']) ? $map['notification:*'] : null;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function contextPropertyNames(): array
    {
        $ref = new ReflectionClass(WhatsAppSendContext::class);
        $names = [];
        foreach ($ref->getProperties() as $prop) {
            if ($prop->getDeclaringClass()->getName() === WhatsAppSendContext::class) {
                $names[] = $prop->getName();
            }
        }

        return $names;
    }
}
