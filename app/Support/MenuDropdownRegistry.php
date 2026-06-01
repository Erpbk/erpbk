<?php

namespace App\Support;

use App\Models\Settings;

/**
 * Sidebar modules that use a parent menu-toggle with submenu items.
 * Tree source: config/company_module_tree.php (child keys match menu_labels.defaults).
 */
class MenuDropdownRegistry
{
    /**
     * @return array{parent_key: string, parent_label: string, parent_default: string, children: list<array{key: string, label: string, default: string}>}|null
     */
    public static function contextForModuleKey(string $moduleKey): ?array
    {
        $moduleKey = ErpModuleRegistry::normalizeKey($moduleKey);
        $tree = config('company_module_tree', []);

        foreach ($tree as $node) {
            $parentKey = (string) ($node['key'] ?? '');
            $children = $node['children'] ?? [];
            if ($children === []) {
                continue;
            }

            if ($parentKey === $moduleKey || in_array($moduleKey, $children, true)) {
                return self::buildContext($parentKey, $children);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $childKeys
     * @return array{parent_key: string, parent_label: string, parent_default: string, children: list<array{key: string, label: string, default: string}>}
     */
    protected static function buildContext(string $parentKey, array $childKeys): array
    {
        $defaults = config('menu_labels.defaults', []);
        $labels = Settings::getMenuLabels();

        $children = [];
        foreach ($childKeys as $key) {
            $key = ErpModuleRegistry::normalizeKey((string) $key);
            if (! array_key_exists($key, $defaults)) {
                continue;
            }
            $children[] = [
                'key' => $key,
                'label' => $labels[$key] ?? $defaults[$key],
                'default' => $defaults[$key],
            ];
        }

        return [
            'parent_key' => $parentKey,
            'parent_label' => $labels[$parentKey] ?? $defaults[$parentKey] ?? '',
            'parent_default' => $defaults[$parentKey] ?? ucwords(str_replace('_', ' ', $parentKey)),
            'children' => $children,
        ];
    }
}
