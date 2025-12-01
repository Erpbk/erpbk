<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class CloudwaysServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Cloudways-specific configurations
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure session settings for Cloudways
        $this->configureSessionForCloudways();

        // Configure cache settings
        $this->configureCacheForCloudways();

        // Set proper timezone
        $this->configureTimezone();
    }

    /**
     * Configure session settings specifically for Cloudways hosting
     */
    protected function configureSessionForCloudways()
    {
        if (env('CLOUDWAYS_SERVER', false)) {
            // Override session configuration for Cloudways
            Config::set('session.lifetime', env('SESSION_LIFETIME', 1440));
            Config::set('session.secure', request()->isSecure());
            Config::set('session.http_only', true);
            Config::set('session.same_site', 'lax');

            // Use database sessions if configured
            if (config('cloudways.session_fixes.use_database_sessions')) {
                Config::set('session.driver', 'database');
            }
        }
    }

    /**
     * Configure cache settings for Cloudways
     */
    protected function configureCacheForCloudways()
    {
        if (env('CLOUDWAYS_SERVER', false)) {
            $prefix = config('cloudways.cache_prefix', 'cloudways_');
            Config::set('cache.prefix', $prefix);
        }
    }

    /**
     * Configure timezone for Cloudways server
     */
    protected function configureTimezone()
    {
        $timezone = config('cloudways.server_timezone', 'UTC');
        if ($timezone) {
            Config::set('app.timezone', $timezone);
            date_default_timezone_set($timezone);
        }
    }
}
