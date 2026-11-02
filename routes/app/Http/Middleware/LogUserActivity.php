<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Reset per-request logging state before the controller runs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        ActivityLogger::resetRequestFlag();

        return $next($request);
    }

    /**
     * Log mutating HTTP actions after a successful response.
     * Skips when ActivityLogger already recorded model/controller activity.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        if ($response->getStatusCode() >= 400) {
            return;
        }

        if (ActivityLogger::wasLoggedThisRequest()) {
            return;
        }

        $route = $request->route();
        if (!$route || $this->shouldSkipLogging($request, $route->getName() ?? '')) {
            return;
        }

        $action = $this->determineAction($request->method(), $route->getName() ?? '');
        if ($action === null) {
            return;
        }

        $moduleName = $this->extractModuleName($route->getName() ?? '', $route);
        $routeParameters = $route->parameters();
        unset($routeParameters['company_slug']);

        ActivityLogger::logAsync(
            $action,
            $moduleName,
            null,
            [
                'source' => 'http',
                'route' => $route->getName(),
                'url' => $request->path(),
                'method' => $request->method(),
                'route_parameters' => $routeParameters,
                'input' => $this->sanitizeInput($request->except($this->sensitiveInputKeys())),
            ]
        );
    }

    private function isAuthenticated(): bool
    {
        return Auth::guard('web')->check() || Auth::guard('admin')->check();
    }

    /**
     * Keys never stored in activity log payloads.
     *
     * @return list<string>
     */
    private function sensitiveInputKeys(): array
    {
        return [
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'new_password_confirmation',
            'otp',
            'otp_code',
            'verification_code',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function sanitizeInput(array $input): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $sanitized[$key] = [
                    'name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'mime' => $value->getClientMimeType(),
                ];
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);

        foreach ($this->sensitiveInputKeys() as $sensitive) {
            if ($lower === strtolower($sensitive)) {
                return true;
            }
        }

        return str_contains($lower, 'password')
            || str_contains($lower, 'secret')
            || str_contains($lower, 'token');
    }

    private function shouldSkipLogging(Request $request, string $routeName): bool
    {
        if ($routeName === '') {
            return true;
        }

        $skipPrefixes = [
            'activity-logs.',
            'settings-panel.activity-logs.',
            'settings-panel.trash.index',
            'settings-panel.trash.stats',
            'settings-panel.trash.show',
            'login',
            'logout',
            'register',
            'password.',
            'verification.',
            'sanctum.',
            'api.',
        ];

        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix) || str_contains($routeName, $prefix)) {
                return true;
            }
        }

        if ($request->isMethod('GET')) {
            $readOnlyPatterns = [
                '.index',
                '.show',
                '.create',
                '.edit',
                'table-body',
                'accordion',
                'config-schema',
                'statistics',
                'library-search',
                'field-values',
                'types-table-body',
                'fields-table-body',
                'categories-table-body',
                'document-types-table-body',
                'rider-top-accordion-body',
                'cheque-top-accordion-body',
                'employee-top-accordion-body',
                'bike-top-accordion-body',
            ];

            foreach ($readOnlyPatterns as $pattern) {
                if (str_contains($routeName, $pattern)) {
                    return true;
                }
            }

            return !$this->isMutatingGetRoute($routeName);
        }

        return false;
    }

    private function isMutatingGetRoute(string $routeName): bool
    {
        $mutatingPatterns = [
            'toggle-active',
            'toggle-status',
            'set-default',
            'restore',
            'approve',
            'reject',
        ];

        foreach ($mutatingPatterns as $pattern) {
            if (str_contains($routeName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function extractModuleName(string $routeName, $route): string
    {
        if (str_starts_with($routeName, 'settings-panel.')) {
            $parts = explode('.', $routeName);
            $segment = $parts[1] ?? 'settings';

            if (in_array($segment, ['module-settings', 'module-top-bar'], true)) {
                $module = $route->parameter('module');
                if (is_string($module) && $module !== '') {
                    $label = ucfirst(str_replace(['_', '-'], ' ', $module));
                    return $segment === 'module-top-bar' ? $label . ' Top Bar' : $label . ' Settings';
                }
            }

            if ($segment === 'menu-icons') {
                return 'Menu Icons';
            }

            return ucfirst(str_replace(['_', '-'], ' ', $segment));
        }

        if (str_starts_with($routeName, 'admin.')) {
            $parts = explode('.', $routeName);
            $segment = $parts[1] ?? 'admin';

            return 'Admin: ' . ucfirst(str_replace(['_', '-'], ' ', $segment));
        }

        if (preg_match('/^([a-z0-9_-]+)\./', $routeName, $matches)) {
            return ucfirst(str_replace(['_', '-'], ' ', $matches[1]));
        }

        return 'Application';
    }

    private function determineAction(string $method, string $routeName): ?string
    {
        $routeActions = [
            'force-destroy' => 'force_deleted',
            'force_destroy' => 'force_deleted',
            'destroy' => 'deleted',
            'restore' => 'restored',
            'reorder' => 'reordered',
            'toggle-active' => 'toggled',
            'toggle-status' => 'toggled',
            'set-default' => 'set_default',
            'approve' => 'approved',
            'reject' => 'rejected',
            'send-otp' => 'sent_otp',
            'verify-otp' => 'verified_otp',
            'import' => 'imported',
            'export' => 'exported',
            'logged_in' => 'logged_in',
            'logged_out' => 'logged_out',
        ];

        foreach ($routeActions as $pattern => $action) {
            if (str_contains($routeName, $pattern)) {
                return $action;
            }
        }

        if (str_contains($routeName, '.store') || str_ends_with($routeName, '.store')) {
            return 'created';
        }

        if (str_contains($routeName, '.update') || str_ends_with($routeName, '.update')) {
            return 'updated';
        }

        if (str_contains($routeName, '.destroy') || str_ends_with($routeName, '.destroy')) {
            return 'deleted';
        }

        if (str_contains($routeName, 'store-') || str_contains($routeName, '.store-')) {
            return 'created';
        }

        if (str_contains($routeName, 'update-') || str_contains($routeName, '.update-')) {
            return 'updated';
        }

        if (str_contains($routeName, 'delete-') || str_contains($routeName, '.delete-')) {
            return 'deleted';
        }

        if (str_contains($routeName, 'destroy-') || str_contains($routeName, '.destroy-')) {
            return 'deleted';
        }

        return match (strtoupper($method)) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => null,
        };
    }
}
