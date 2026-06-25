<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Tabler Icons (online library) — names match CSS classes used in tabler-icons.css (ti-*).
 */
class TablerIconLibrary
{
    /**
     * @return list<string>
     */
    public static function iconNames(): array
    {
        return Cache::remember(self::cacheKey(), 60 * 60 * 24 * 7, function () {
            $url = (string) config('menu_icons.library.cdn_icons_json', '');
            if ($url !== '') {
                try {
                    $context = stream_context_create([
                        'http' => ['timeout' => 15, 'user_agent' => 'ERP-MenuIconLibrary/1.0'],
                    ]);
                    $raw = @file_get_contents($url, false, $context);
                    if ($raw !== false) {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded) && $decoded !== []) {
                            return array_values(array_keys($decoded));
                        }
                    }
                } catch (\Throwable $e) {
                    // Fall through to bundled list.
                }
            }

            return self::fallbackIconNames();
        });
    }

    /**
     * @return list<array{name: string, class: string, label: string}>
     */
    public static function search(?string $query, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('menu_icons.library.search_limit', 80);
        $limit = max(10, min(200, $limit));
        $query = mb_strtolower(trim((string) $query));
        $query = str_replace(' ', '-', $query);

        $names = self::iconNames();
        $popular = array_flip(self::popularIconClasses());
        $results = [];

        if ($query === '') {
            foreach (array_keys($popular) as $class) {
                $name = ltrim($class, 'ti-');
                if (in_array($name, $names, true)) {
                    $results[] = self::formatEntry($name);
                }
                if (count($results) >= $limit) {
                    return $results;
                }
            }
        }

        foreach ($names as $name) {
            if ($query !== '' && ! str_contains($name, $query)) {
                continue;
            }
            $results[] = self::formatEntry($name);
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    public static function isValidClass(string $class): bool
    {
        $class = self::normalizeClass($class);
        if ($class === '') {
            return false;
        }
        $name = ltrim($class, 'ti-');
        static $nameSet = null;
        if ($nameSet === null) {
            $nameSet = array_flip(self::iconNames());
        }

        return isset($nameSet[$name]);
    }

    public static function normalizeClass(string $class): string
    {
        $class = trim($class);
        if ($class === '') {
            return '';
        }
        if (! str_starts_with($class, 'ti-')) {
            $class = 'ti-' . ltrim($class, 'ti');
        }

        if ($class === 'ti-passport') {
            $class = 'ti-e-passport';
        }

        return preg_match('/^ti-[a-z0-9-]+$/', $class) ? $class : '';
    }

    /**
     * @return array{name: string, class: string, label: string}
     */
    protected static function formatEntry(string $name): array
    {
        return [
            'name' => $name,
            'class' => 'ti-' . $name,
            'label' => ucwords(str_replace('-', ' ', $name)),
        ];
    }

    /**
     * @return list<string>
     */
    protected static function popularIconClasses(): array
    {
        $fromConfig = config('menu_icons.presets', []);
        $fromDefaults = array_values(config('menu_icons.defaults', []));

        return array_values(array_unique(array_merge(array_keys($fromConfig), $fromDefaults)));
    }

    /**
     * @return list<string>
     */
    protected static function fallbackIconNames(): array
    {
        $names = [];
        foreach (self::popularIconClasses() as $class) {
            $names[] = ltrim($class, 'ti-');
        }
        foreach (array_keys(config('menu_icons.defaults', [])) as $key) {
            $class = config('menu_icons.defaults.' . $key);
            if (is_string($class)) {
                $names[] = ltrim($class, 'ti-');
            }
        }

        return array_values(array_unique($names));
    }

    protected static function cacheKey(): string
    {
        return 'tabler_icon_names_v1';
    }

    public static function clearCache(): void
    {
        Cache::forget(self::cacheKey());
    }
}
