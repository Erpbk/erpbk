<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only log for authenticated users
        if (Auth::check()) {
            $this->logRequest($request);
        }

        return $next($request);
    }

    /**
     * Log the current request if it's a significant action.
     */
    private function logRequest(Request $request): void
    {
        $method = $request->method();
        $route = $request->route();

        // Skip logging for certain routes and methods
        if ($this->shouldSkipLogging($request, $route)) {
            return;
        }

        // Extract module name from route
        $moduleName = $this->extractModuleName($route);

        if ($moduleName) {
            $action = $this->determineAction($method, $route);

            if ($action) {
                ActivityLogger::custom(
                    $action,
                    $moduleName,
                    null,
                    [
                        'route' => $route->getName(),
                        'url' => $request->fullUrl(),
                        'method' => $method,
                        'user_agent' => $request->userAgent()
                    ]
                );
            }
        }
    }

    /**
     * Determine if we should skip logging this request.
     */
    private function shouldSkipLogging(Request $request, $route): bool
    {
        if (!$route) {
            return true;
        }

        $routeName = $route->getName();

        // Skip certain routes
        $skipRoutes = [
            'activity-logs.',
            'login',
            'logout',
            'register',
            'password.',
            'verification.',
            'sanctum.',
            'api.',
        ];

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($routeName, $skipRoute)) {
                return true;
            }
        }

        // Skip GET requests to index/show pages (too noisy)
        if (
            $request->isMethod('GET') &&
            (str_contains($routeName, '.index') || str_contains($routeName, '.show'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Extract module name from route.
     */
    private function extractModuleName($route): ?string
    {
        if (!$route) {
            return null;
        }

        $routeName = $route->getName();

        // Extract module from route name (e.g., 'users.index' -> 'Users')
        if (preg_match('/^([a-z_]+)\./', $routeName, $matches)) {
            return ucfirst(str_replace('_', ' ', $matches[1]));
        }

        return null;
    }

    /**
     * Determine the action based on HTTP method and route.
     */
    private function determineAction(string $method, $route): ?string
    {
        $routeName = $route->getName() ?? '';

        switch ($method) {
            case 'POST':
                return str_contains($routeName, '.store') ? 'created' : null;
            case 'PUT':
            case 'PATCH':
                return str_contains($routeName, '.update') ? 'updated' : null;
            case 'DELETE':
                return str_contains($routeName, '.destroy') ? 'deleted' : null;
            default:
                return null;
        }
    }
}
