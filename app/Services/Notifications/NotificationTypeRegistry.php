<?php

namespace App\Services\Notifications;

class NotificationTypeRegistry
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return config('notification_types.types', []);
    }

    public function get(string $typeKey): ?array
    {
        $types = $this->all();

        return $types[$typeKey] ?? null;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /** @return array<string, array<string, mixed>> */
    public function byTrigger(string $trigger): array
    {
        return array_filter(
            $this->all(),
            fn (array $def) => ($def['trigger'] ?? null) === $trigger
        );
    }

    public function label(string $typeKey): string
    {
        return (string) ($this->get($typeKey)['label'] ?? $typeKey);
    }

    /** @return array<string, array<string, array<string, mixed>>> module => [typeKey => def] */
    public function groupedByModule(): array
    {
        $grouped = [];
        foreach ($this->all() as $typeKey => $def) {
            $module = (string) ($def['module'] ?? 'other');
            $grouped[$module][$typeKey] = $def;
        }
        ksort($grouped);

        return $grouped;
    }

    public function moduleLabel(string $moduleKey): string
    {
        $labels = config('menu_labels.defaults', []);
        if (isset($labels[$moduleKey])) {
            return (string) $labels[$moduleKey];
        }

        return ucwords(str_replace('_', ' ', $moduleKey));
    }
}
