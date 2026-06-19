<?php

namespace App\Support;

use App\Models\Settings;

class ModuleMenuIcon
{
    /**
     * HTML for sidebar / settings panel menu (Tabler icon or uploaded image).
     */
    public static function render(string $menuKey): string
    {
        $icon = Settings::getMenuIcon($menuKey);

        if (($icon['type'] ?? 'class') === 'image' && ! empty($icon['url'])) {
            return '<img src="' . e($icon['url']) . '" alt="" class="menu-icon menu-icon-custom" width="22" height="22" loading="lazy" />';
        }

        $class = e($icon['class'] ?? 'ti-adjustments-alt');
        if ($class === 'ti-passport') {
            $class = 'ti-e-passport';
        }

        return '<i class="menu-icon tf-icons ti ' . $class . '"></i>';
    }

    /**
     * Tabler class for templates that only support &lt;i&gt; (fallback when image set).
     */
    public static function tablerClass(string $menuKey): string
    {
        $icon = Settings::getMenuIcon($menuKey);

        if (($icon['type'] ?? 'class') === 'image') {
            $defaults = config('menu_icons.defaults', []);

            return $defaults[$menuKey] ?? 'ti-adjustments-alt';
        }

        return $icon['class'] ?? config('menu_icons.defaults.' . $menuKey, 'ti-adjustments-alt');
    }

    public static function isImage(string $menuKey): bool
    {
        $icon = Settings::getMenuIcon($menuKey);

        return ($icon['type'] ?? 'class') === 'image' && ! empty($icon['url']);
    }

    public static function imageUrl(string $menuKey): ?string
    {
        $icon = Settings::getMenuIcon($menuKey);

        return self::isImage($menuKey) ? ($icon['url'] ?? null) : null;
    }
}
