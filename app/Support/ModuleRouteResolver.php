<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModuleRouteResolver
{
    /**
     * Map route name prefixes to ERP module keys for top-bar config.
     *
     * @var array<string, string>
     */
    protected static array $routeAliases = [
        'bikes' => 'bike_list',
        'bike' => 'bike_list',
        'riders' => 'riders',
        'rider' => 'riders',
        'cheque' => 'cheques',
        'cheques' => 'cheques',
        'employee' => 'employees',
        'employees' => 'employees',
        'customer' => 'customers',
        'customers' => 'customers',
        'vendor' => 'vendors',
        'vendors' => 'vendors',
        'garage' => 'garages',
        'garages' => 'garages',
        'sim' => 'sims',
        'sims' => 'sims',
        'expense' => 'expenses',
        'expenses' => 'expenses',
        'rtaFines' => 'rta_fines_unpaid',
    ];

    public static function fromRequest(?Request $request = null): ?string
    {
        $request = $request ?? request();
        $route = $request->route();

        if ($route === null) {
            return null;
        }

        $moduleParam = $route->parameter('module');
        if (is_string($moduleParam) && $moduleParam !== '') {
            $key = ErpModuleRegistry::normalizeKey($moduleParam);
            if (ErpModuleRegistry::hasTopBar($key)) {
                return $key;
            }
        }

        $name = (string) $route->getName();
        if ($name === '') {
            return null;
        }

        if (preg_match('/^settings-panel\.module-settings\.index$/', $name)) {
            $key = ErpModuleRegistry::normalizeKey((string) $moduleParam);

            return ErpModuleRegistry::hasTopBar($key) ? $key : null;
        }

        if (preg_match('/^([a-z0-9_]+)\.index$/i', $name, $matches)) {
            $segment = ErpModuleRegistry::normalizeKey($matches[1]);
            $key = self::$routeAliases[$segment] ?? $segment;

            if (ErpModuleRegistry::hasTopBar($key)) {
                return $key;
            }

            if (ErpModuleRegistry::hasTopBar($segment)) {
                return $segment;
            }
        }

        foreach (self::$routeAliases as $prefix => $moduleKey) {
            if (Str::startsWith($name, $prefix . '.')) {
                if ($prefix === 'rtaFines' && Str::contains($name, '.paid')) {
                    return ErpModuleRegistry::hasTopBar('rta_fines_paid') ? 'rta_fines_paid' : null;
                }

                return ErpModuleRegistry::hasTopBar($moduleKey) ? $moduleKey : null;
            }
        }

        return null;
    }
}
