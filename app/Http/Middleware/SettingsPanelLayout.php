<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SettingsPanelLayout
{
    /**
     * Share the settings panel layout so views use it when in the panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('layout', 'layouts.settingsPanelLayout');
        View::share('settings_panel', true);
        return $next($request);
    }
}
